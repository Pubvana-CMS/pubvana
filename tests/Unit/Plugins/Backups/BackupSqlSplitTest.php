<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Backups;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Tests\Support\TestCase;

/**
 * M6: backup restore must not split SQL inside string literals.
 *
 * restoreViaPHP() used to split dumps on `;\s*\n`, which corrupted any
 * stored value containing that sequence (page bodies, comments, form
 * fields). splitSqlStatements() now tracks MySQL lexical state; these
 * tests pin the hostile cases and a full round-trip through PDO.
 */
#[CoversClass(BackupService::class)]
final class BackupSqlSplitTest extends TestCase
{
    /**
     * @param array{host: string, port: int, dbname: string, user: string, password: string} $credentials
     */
    private function service(?PDO $pdo = null, array $credentials = []): BackupService
    {
        return new BackupService(
            $pdo ?? new PDO('sqlite::memory:'),
            [
                'backup_path'       => sys_get_temp_dir() . '/backups_sql_split_test',
                'max_backups'       => 5,
                'backup_dirs'       => [],
                'protected_configs' => [],
            ],
            $credentials === [] ? ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'test', 'user' => 'test', 'password' => ''] : $credentials,
        );
    }

    /**
     * @return list<string>
     */
    private function split(BackupService $service, string $sql): array
    {
        /** @var list<string> $statements */
        $statements = $this->invoke($service, 'splitSqlStatements', [$sql]);

        return $statements;
    }

    public function testLiteralContainingSemicolonNewlineStaysIntact(): void
    {
        $sql = "INSERT INTO `pages` (`body`) VALUES ('line one;\nline two');\n"
            . "INSERT INTO `pages` (`body`) VALUES ('other');\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(2, $statements);
        $this->assertStringContainsString("line one;\nline two", $statements[0]);
        $this->assertSame("INSERT INTO `pages` (`body`) VALUES ('other')", $statements[1]);
    }

    public function testEscapedBackslashQuoteStaysInsideString(): void
    {
        $sql = "INSERT INTO `t` (`v`) VALUES ('It\\'s fine; still one');\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('It\\\'s fine; still one', $statements[0]);
    }

    public function testDoubledQuoteStaysInsideString(): void
    {
        $sql = "INSERT INTO `t` (`v`) VALUES ('a''b; c');\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(1, $statements);
        $this->assertSame("INSERT INTO `t` (`v`) VALUES ('a''b; c')", $statements[0]);
    }

    public function testBackslashEscapedSemicolonStaysInsideString(): void
    {
        $sql = "INSERT INTO `t` (`v`) VALUES ('end\\; still in');\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(1, $statements);
        $this->assertSame("INSERT INTO `t` (`v`) VALUES ('end\\; still in')", $statements[0]);
    }

    public function testDoubleQuotedStringWithSemicolonStaysIntact(): void
    {
        $sql = "SET @x = \"a;b\";\nSELECT 1;\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(2, $statements);
        $this->assertSame('SET @x = "a;b"', $statements[0]);
    }

    public function testBacktickIdentifierWithSemicolonStaysIntact(): void
    {
        $sql = "INSERT INTO `my;table` (`v`) VALUES ('x');\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(1, $statements);
        $this->assertSame("INSERT INTO `my;table` (`v`) VALUES ('x')", $statements[0]);
    }

    public function testCommentOnlyFragmentsAreDropped(): void
    {
        $sql = "--\n-- Dump header\n--\n\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n"
            . "# hash comment line\n"
            . "SET FOREIGN_KEY_CHECKS=1;\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(2, $statements);
        $this->assertSame('SET FOREIGN_KEY_CHECKS=0', $statements[0]);
        $this->assertSame('SET FOREIGN_KEY_CHECKS=1', $statements[1]);
    }

    public function testLineCommentAboveStatementDoesNotSwallowIt(): void
    {
        $sql = "-- note\nSELECT 1;\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(1, $statements);
        $this->assertSame("SELECT 1", $statements[0]);
    }

    public function testDoubleDashWithoutWhitespaceIsNotAComment(): void
    {
        $sql = "SELECT 1--1;\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(1, $statements);
        $this->assertSame('SELECT 1--1', $statements[0]);
    }

    public function testConditionalCommentStaysWhole(): void
    {
        $sql = "/*!40101 SET NAMES utf8mb4 */;\n"
            . "SET FOREIGN_KEY_CHECKS=1;\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(2, $statements);
        $this->assertSame('/*!40101 SET NAMES utf8mb4 */', $statements[0]);
    }

    public function testMysqldumpShapedSequenceSplitsCorrectly(): void
    {
        $sql = "/*!40101 SET @saved_cs_client = @@character_set_client */;\n"
            . "/*!40101 SET character_set_client = utf8mb4 */;\n"
            . "DROP TABLE IF EXISTS `pages`;\n"
            . "CREATE TABLE `pages` (\n"
            . "  `id` int NOT NULL AUTO_INCREMENT,\n"
            . "  `body` text NOT NULL,\n"
            . "  PRIMARY KEY (`id`)\n"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n"
            . "INSERT INTO `pages` (`id`, `body`) VALUES\n"
            . "  (1, 'has ;\\n inside'),\n"
            . "  (2, 'plain; value');\n"
            . "/*!40101 SET character_set_client = @saved_cs_client */;\n";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(6, $statements);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `pages`', $statements[2]);
        $this->assertStringContainsString('ENGINE=InnoDB', $statements[3]);
        $this->assertStringContainsString("(1, 'has ;\\n inside')", $statements[4]);
        $this->assertStringContainsString("(2, 'plain; value')", $statements[4]);
        $this->assertSame('/*!40101 SET character_set_client = @saved_cs_client */', $statements[5]);
    }

    public function testTrailingStatementWithoutSemicolonIsKept(): void
    {
        $sql = "SELECT 1;\nSELECT 2";

        $statements = $this->split($this->service(), $sql);

        $this->assertCount(2, $statements);
        $this->assertSame('SELECT 2', $statements[1]);
    }

    public function testRoundTripThroughRestoreViaPhpPreservesHostileLiteral(): void
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('CREATE TABLE pages (body TEXT)');

        $service = $this->service($pdo);
        // Reproduce dumpViaPHP()'s quoting: the value contains `;\n`.
        $body = "first line;\nsecond line; with ''quotes'' and \\backslash";
        $sql  = 'INSERT INTO `pages` (`body`) VALUES (' . $pdo->quote($body) . ");\n";

        $this->invoke($service, 'restoreViaPHP', [$sql]);

        $stored = (string) $pdo->query('SELECT body FROM pages')->fetchColumn();
        $this->assertSame($body, $stored);
    }

    public function testEmptyAndCommentOnlyInputYieldNothing(): void
    {
        $this->assertSame([], $this->split($this->service(), ''));
        $this->assertSame([], $this->split($this->service(), "-- header\n-- only\n"));
        $this->assertSame([], $this->split($this->service(), "/* block */\n\n"));
    }

    /**
     * mysqldump must never emit DELIMITER blocks (triggers/routines/events):
     * the pure-PHP restore fallback cannot parse them. A shim on PATH
     * captures the real argv dumpViaMysqldump() builds.
     */
    public function testMysqldumpArgvExcludesTriggersRoutinesEvents(): void
    {
        $dir = sys_get_temp_dir() . '/backups_mysqldump_shim_' . getmypid();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $argvFile = $dir . '/argv.txt';
        $shim = $dir . '/mysqldump';
        file_put_contents($shim, "#!/bin/sh\nprintf '%s\\n' \"\$@\" > " . escapeshellarg($argvFile) . "\necho '-- shim dump'\n");
        chmod($shim, 0775);

        $path = getenv('PATH') ?: '';
        putenv('PATH=' . $dir . PATH_SEPARATOR . $path);
        $dump = $this->invoke($this->service(), 'dumpViaMysqldump');
        putenv('PATH=' . $path);

        $this->assertIsString($dump);
        $this->assertSame('-- shim dump', trim((string) $dump));

        /** @var list<string> $shimArgv */
        $shimArgv = file($argvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $this->assertContains('--skip-triggers', $shimArgv);
        $this->assertContains('--skip-routines', $shimArgv);
        $this->assertContains('--skip-events', $shimArgv);

        @unlink($argvFile);
        @unlink($shim);
        @rmdir($dir);
    }
}
