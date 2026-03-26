# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Hard Rules - These applies across all sessions and survives context compaction.

1 Ask Before Building — Hard Rule

**Never write code when discussing, planning, or deciding anything.** 

Do the full research on a question and offer **all** options or posibilities. 

Automatic building without explicit go-ahead is not acceptable.

2: Never break MVC convension. 

3: Do Not take shortcuts 

4: Verify no other (web or api) controllers use the API endpoint(includes models) we're woring on/calling. If another web/api controller uses the same API endpoint make sure the changes do not break other calls to the API or plan to fix all conflicts at once.


5: Don't assume or make judgement calls. Stop and ask if it hasn't been discussed. The user is picky.

6: All subagents are Sonnet
