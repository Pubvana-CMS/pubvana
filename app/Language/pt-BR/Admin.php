<?php

/**
 * Pubvana CMS - Admin language strings (Brazilian Portuguese)
 *
 * Convention: snake_case v1 key → camelCase CI4 key
 * Keys are grouped by feature section with comment headers.
 *
 * Usage: lang('Admin.keyName')
 */

return [

    // =========================================================================
    // Common UI - buttons, labels, confirmations, status badges
    // =========================================================================

    // Buttons
    'save'              => 'Salvar',
    'saveChanges'       => 'Salvar Alterações',
    'cancel'            => 'Cancelar',
    'edit'              => 'Editar',
    'delete'            => 'Excluir',
    'create'            => 'Criar',
    'add'               => 'Adicionar',
    'back'              => 'Voltar',
    'view'              => 'Ver',
    'apply'             => 'Aplicar',
    'install'           => 'Instalar',
    'update'            => 'Atualizar',
    'refresh'           => 'Atualizar',
    'activate'          => 'Ativar',
    'deactivate'        => 'Desativar',
    'enable'            => 'Habilitar',
    'disable'           => 'Desabilitar',
    'disabled'          => 'Desabilitado',
    'approve'           => 'Aprovar',
    'spam'              => 'Spam',
    'trash'             => 'Lixeira',
    'restore'           => 'Restaurar',
    'dismiss'           => 'Dispensar',
    'recheck'           => 'Verificar Novamente',
    'clickToCopy'       => 'Clique para Copiar',
    'download'          => 'Baixar',
    'upload'            => 'Enviar',
    'import'            => 'Importar',
    'export'            => 'Exportar',
    'publish'           => 'Publicar',
    'unpublish'         => 'Despublicar',
    'logout'            => 'Sair',
    'viewSite'          => 'Ver Site',
    'newPost'           => 'Nova Publicação',
    'buyNow'            => 'Comprar Agora',
    'visitStore'        => 'Visitar Loja',
    'loadMore'          => 'Carregar Mais',

    // Table headers / labels
    'title'             => 'Título',
    'name'              => 'Nome',
    'slug'              => 'Slug',
    'status'            => 'Status',
    'date'              => 'Data',
    'actions'           => 'Ações',
    'author'            => 'Autor',
    'views'             => 'Visualizações',
    'type'              => 'Tipo',
    'url'               => 'URL',
    'description'       => 'Descrição',
    'role'              => 'Função',
    'email'             => 'E-mail',
    'username'          => 'Nome de usuário',
    'active'            => 'Ativo',
    'version'           => 'Versão',
    'size'              => 'Tamanho',
    'clicks'            => 'Cliques',
    'total'             => 'Total',
    'platform'          => 'Plataforma',
    'label'             => 'Rótulo',
    'order'             => 'Ordem',
    'source'            => 'Fonte',
    'content'           => 'Conteúdo',
    'excerpt'           => 'Resumo',
    'details'           => 'Detalhes',
    'contentType'       => 'Tipo de conteúdo',
    'seo'               => 'SEO',
    'metaTitle'         => 'Título Meta',
    'metaDescription'   => 'Descrição Meta',

    // Status badges
    'published'         => 'Publicado',
    'draft'             => 'Rascunho',
    'scheduled'         => 'Agendado',
    'pending'           => 'Pendente',
    'safe'              => 'Seguro',
    'notSafe'           => 'Não Seguro',
    'malicious'         => 'Malicioso',
    'safetyUnknown'     => 'Desconhecido',
    'inactive'          => 'Inativo',
    'installed'         => 'Instalado',
    'free'              => 'Gratuito',
    'premium'           => 'Premium',
    'all'               => 'Todos',

    // Confirmations
    'confirmDelete'         => 'Tem certeza de que deseja excluir este item?',
    'confirmDeletePost'     => 'Excluir esta publicação?',
    'confirmDeletePage'     => 'Excluir esta página?',
    'confirmDeleteComment'  => 'Excluir este comentário permanentemente?',
    'confirmDeleteUser'     => 'Excluir este usuário?',
    'confirmDeleteMedia'    => 'Excluir?',
    'confirmDeleteBackup'   => 'Excluir este arquivo de backup?',
    'confirmBulkAction'     => 'Aplicar ação em massa às publicações selecionadas?',

    // Empty states
    'noPostsYet'        => 'Nenhuma publicação ainda. {0}',
    'noResultsFound'    => 'Nenhum resultado encontrado.',
    'noCommentsYet'     => 'Nenhum comentário pendente.',
    'noMediaYet'        => 'Nenhuma mídia ainda.',
    'noItemsFound'      => 'Nenhum item encontrado no marketplace.',
    'noCategoriesYet'   => 'Nenhuma categoria ainda.',
    'noTagsYet'         => 'Nenhuma tag ainda.',
    'noRevisionsYet'    => 'Nenhuma revisão encontrada.',

    // Misc common
    'permissionDenied'  => 'Permissão negada.',
    'notFound'          => 'Registro não encontrado.',
    'commasSeparated'   => 'Separados por vírgula',
    'optional'          => 'Opcional',
    'required'          => 'Obrigatório',
    'enabled'           => 'Habilitado',
    'selected'          => '{0} publicação(ões) selecionada(s)',
    'published_count'   => '{0} publicado(s)',
    'pending_count'     => '{0} pendente(s)',

    // =========================================================================
    // Navigation / Sidebar labels
    // =========================================================================

    'navDashboard'      => 'Painel',
    'navContent'        => 'Conteúdo',
    'navAppearance'     => 'Aparência',
    'navUsersAndSite'   => 'Usuários e Site',
    'navTools'          => 'Ferramentas',
    'navMarketplace'    => 'Marketplace',
    'navPlugins'        => 'Plugins',
    'navPosts'          => 'Publicações',
    'navSchedule'       => 'Agendamento',
    'navPages'          => 'Páginas',
    'navCategories'     => 'Categorias',
    'navTags'           => 'Tags',
    'navComments'       => 'Comentários',
    'navMedia'          => 'Mídia',
    'navImport'         => 'Importar',
    'navThemes'         => 'Temas',
    'navWidgets'        => 'Widgets',
    'navNavigation'     => 'Navegação',
    'navUsers'          => 'Usuários',
    'navSocialLinks'    => 'Links Sociais',
    'navRedirects'      => 'Redirecionamentos',
    'navLanguages'      => 'Idiomas',
    'navSettings'       => 'Configurações',
    'navAnalytics'      => 'Análises',
    'navAffiliates'     => 'Links de Afiliados',
    'navBrokenLinks'    => 'Links Quebrados',
    'navActivityLog'    => 'Registro de Atividades',
    'navBackup'         => 'Backup e Exportação',
    'navUpdates'        => 'Atualizações',
    'navBrowse'         => 'Navegar',
    'navLicenses'       => 'Licenças',
    'navPubvanaStore'   => 'Loja Pubvana',
    'navUpdateAvailable'=> 'Atualização Disponível',

    // =========================================================================
    // Admin layout
    // =========================================================================

    'logoutModalTitle'  => 'Pronto para Sair?',
    'logoutModalBody'   => 'Selecione "Sair" abaixo para encerrar sua sessão.',
    'footerCopyright'   => 'Pubvana CMS',

    // =========================================================================
    // Dashboard
    // =========================================================================

    'dashboardTitle'        => 'Painel',
    'dashStats'             => 'Estatísticas',
    'dashPosts'             => 'Publicações',
    'dashPages'             => 'Páginas',
    'dashComments'          => 'Comentários',
    'dashUsers'             => 'Usuários',
    'dashRecentPosts'       => 'Publicações Recentes',
    'dashPendingComments'   => 'Comentários Pendentes',
    'dashViewAll'           => 'Ver Todos',
    'dashCreateOne'         => 'Criar uma!',
    'dashNoPosts'           => 'Nenhuma publicação ainda.',
    'dashNoPendingComments' => 'Nenhum comentário pendente.',

    // =========================================================================
    // Posts
    // =========================================================================

    'postsTitle'            => 'Publicações',
    'newPostTitle'          => 'Nova Publicação',
    'editPostTitle'         => 'Editar Publicação: {0}',
    'copyPreviewLink'       => 'Copiar Link de Pré-visualização',
    'backToPosts'           => 'Voltar às Publicações',
    'postTitleField'        => 'Título *',
    'postEditor'            => 'Editor',
    'postHtmlEditor'        => 'Editor HTML',
    'postMarkdown'          => 'Markdown',
    'postExcerpt'           => 'Resumo',
    'postExcerptPlaceholder'=> 'Resumo curto opcional...',
    'postSeoSection'        => 'SEO',
    'postMetaTitle'         => 'Título Meta',
    'postMetaDescription'   => 'Descrição Meta',
    'postPublishSection'    => 'Publicar',
    'postStatus'            => 'Status',
    'postStatusDraft'       => 'Rascunho',
    'postStatusPublished'   => 'Publicado',
    'postStatusScheduled'   => 'Agendado',
    'postScheduledAt'       => 'Data e Hora Agendada',
    'postFeatured'          => 'Publicação em Destaque',
    'postMembersOnly'       => 'Somente para Membros',
    'postShareOnPublish'    => 'Compartilhar nas redes sociais ao publicar',
    'postSaveBtn'           => 'Salvar Publicação',
    'postFeaturedImage'     => 'Imagem em Destaque',
    'postFeaturedImagePlaceholder' => 'URL ou caminho de upload…',
    'postCategories'        => 'Categorias',
    'postTags'              => 'Tags',
    'postTagsPlaceholder'   => 'tag1, tag2, tag3',
    'postRevisions'         => 'Revisões',
    'postRevisionCount'     => '{0} revisão(ões)',
    'postPreview'           => 'Pré-visualizar',
    'postBulkAction'        => '- Selecionar ação -',
    'postBulkPublish'       => 'Publicar',
    'postBulkUnpublish'     => 'Despublicar (definir como Rascunho)',
    'postBulkDelete'        => 'Excluir',

    // Post flash messages
    'postCreated'           => 'Publicação criada com sucesso.',
    'postUpdated'           => 'Publicação atualizada.',
    'scheduledDateMustBeFuture' => 'A data agendada deve ser no futuro.',
    'postDeleted'           => 'Publicação excluída.',
    'postBulkUpdated'       => '{0} publicação(ões) atualizada(s).',
    'postBulkInvalid'       => 'Ação em massa inválida.',
    'postPermission'        => 'Você só pode editar suas próprias publicações.',

    // =========================================================================
    // Revisions
    // =========================================================================

    'revisionsTitle'        => 'Revisões: {0}',
    'revisionTitle'         => 'Revisão — {0}',
    'revisionShowTitle'     => 'Revisão',
    'revisionsBackToPost'   => 'Voltar à Publicação',
    'revisionsBackToList'   => 'Voltar às Revisões',
    'revisionRestored'      => 'Publicação restaurada para a revisão de {0}.',
    'revisionRestoreBtn'    => 'Restaurar esta Revisão',
    'revisionSaved'         => 'Salvo',
    'revisionBy'            => 'Por',
    'revisionOn'            => 'Em',

    // =========================================================================
    // Pages
    // =========================================================================

    'pagesTitle'            => 'Páginas',
    'newPageTitle'          => 'Nova Página',
    'editPageTitle'         => 'Editar Página',
    'pageSlugInUse'         => "O slug '{0}' já está em uso.",
    'pageCannotDelete'      => 'Não é possível excluir esta página.',
    'slugAutoGenHint'       => 'gerado automaticamente a partir do título se deixado em branco',
    'slugCannotChange'      => 'não pode ser alterado',
    'colSystem'             => 'Sistema',
    'system'                => 'Sistema',

    // Page flash messages
    'pageCreated'           => 'Página criada.',
    'pageUpdated'           => 'Página atualizada.',
    'pageDeleted'           => 'Página excluída.',

    // =========================================================================
    // Categories
    // =========================================================================

    'categoriesTitle'       => 'Categorias',
    'newCategoryTitle'      => 'Nova Categoria',
    'editCategoryTitle'     => 'Editar Categoria',
    'categoryName'          => 'Nome',
    'categoryDescription'   => 'Descrição',
    'categoryPostCount'     => 'Contagem de Publicações',

    // Category flash messages
    'categoryCreated'       => 'Categoria criada.',
    'categoryUpdated'       => 'Categoria atualizada.',
    'categoryDeleted'       => 'Categoria excluída.',

    // =========================================================================
    // Tags
    // =========================================================================

    'tagsTitle'             => 'Tags',
    'tagPostCount'          => 'Contagem de Publicações',

    // Tag flash messages
    'tagDeleted'            => 'Tag excluída.',

    // =========================================================================
    // Comments
    // =========================================================================

    'commentsTitle'         => 'Comentários',
    'commentAuthor'         => 'Autor',
    'commentContent'        => 'Comentário',
    'commentPost'           => 'Publicação',
    'commentDate'           => 'Data',
    'commentStatusFilter'   => 'Filtrar por status',

    // Comment flash messages
    'commentApproved'       => 'Comentário aprovado.',
    'commentSpam'           => 'Marcado como spam.',
    'commentTrashed'        => 'Comentário movido para a lixeira.',
    'commentDeleted'        => 'Comentário excluído permanentemente.',

    // =========================================================================
    // Media Library
    // =========================================================================

    'mediaLibrary'          => 'Biblioteca de Mídia',
    'mediaTitle'            => 'Título',
    'mediaAltText'          => 'Texto Alternativo',
    'mediaAltPlaceholder'   => 'Descreva a imagem para acessibilidade',
    'mediaTitlePlaceholder' => 'Título opcional da imagem',
    'mediaImageDetails'     => 'Detalhes da Imagem',
    'mediaSaved'            => 'Salvo!',
    'mediaNoSelection'      => 'Nenhuma imagem selecionada',
    'mediaBrowse'           => 'Navegar na Mídia',
    'mediaRemove'           => 'Remover',
    'mediaUseImage'         => 'Usar Esta Imagem',
    'mediaDropzone'         => 'Arraste e solte a imagem aqui ou clique para navegar',
    'mediaLoading'          => 'Carregando mídia…',
    'mediaEmpty'            => 'Nenhuma mídia enviada ainda.',
    'mediaUpload'           => 'Enviar Mídia',
    'mediaDragDrop'         => 'Arraste e solte arquivos aqui, ou',
    'mediaChooseFiles'      => 'Escolher Arquivos',
    'mediaUploading'        => 'Enviando…',
    'mediaFilename'         => 'Nome do arquivo',
    'mediaSize'             => 'Tamanho',
    'mediaUploadFailed'     => 'Falha no envio: {0}',
    'mediaUploadError'      => 'Erro de envio: {0}',

    // Media flash messages
    'mediaDeleted'          => 'Mídia excluída.',
    'mediaNoValidFile'      => 'Nenhum arquivo válido enviado.',
    'mediaUploadSuccess'    => 'Arquivo enviado com sucesso.',

    // =========================================================================
    // Navigation
    // =========================================================================

    'navigationTitle'       => 'Navegação',
    'navQuickAdd'           => 'Adição Rápida',
    'navQuickAddPlaceholder' => 'Buscar páginas, categorias, plugins...',
    'navItemLabel'          => 'Rótulo',
    'navItemUrl'            => 'URL',
    'navItemTarget'         => 'Destino',
    'navItemOrder'          => 'Ordem de Classificação',
    'navGroupPrimary'       => 'Principal',
    'navGroupFooter'        => 'Rodapé',
    'navSelectGroup'        => 'Selecionar grupo de navegação:',
    'navParent'             => 'Pai',
    'navTopLevel'           => '— Nível superior —',
    'navSameWindow'         => 'Mesma janela',
    'navNewWindow'          => 'Nova janela',
    'navMenuItems'          => 'Itens do Menu',
    'navNoItems'            => 'Nenhum item neste menu.',
    'dragToReorder'         => 'Arrastar para reordenar',

    // Navigation flash messages
    'navItemAdded'          => 'Item de navegação adicionado.',
    'navItemRemoved'        => 'Item de navegação removido.',

    // =========================================================================
    // Themes
    // =========================================================================

    'themesTitle'           => 'Temas',
    'themeOptions'          => 'Opções do Tema',
    'themeActivate'         => 'Ativar',
    'themeOptionsBtn'       => 'Opções',
    'themeActive'           => 'Ativo',
    'themeBy'               => 'Por',
    'themeSupport'          => 'Suporte',
    'themeVersion'          => 'Versão',
    'themeSaveOptions'      => 'Salvar Opções',
    'themeInvalidLicense'   => 'Não é possível ativar o tema - a licença é inválida. Reinstale ou entre em contato com o suporte.',
    'themeValidationFailed' => 'O tema contém código PHP e não pode ser ativado.',
    'noThemesInstalled'     => 'Nenhum tema instalado. Visite o Marketplace para obter temas.',
    'themeUnapprovedTitle'  => 'Ativar Tema Não Aprovado?',
    'themeNotApproved'      => 'Este tema não foi aprovado pelo Pubvana.',
    'themeUnapprovedRisk'   => 'Ativar temas não aprovados pode introduzir riscos de segurança ou problemas de compatibilidade.',
    'themeActivateConfirm'  => 'Tem certeza de que deseja ativá-lo mesmo assim?',
    'themeActivateAnyway'   => 'Ativar Mesmo Assim',
    'themeNoOptions'        => 'Este tema não possui opções configuráveis.',
    'themeCustomize'        => 'Personalizar Tema',

    // Theme flash messages
    'themeActivated'        => 'Tema ativado.',
    'themeOptionsSaved'     => 'Opções salvas.',

    // =========================================================================
    // Theme & Widget License UI
    // =========================================================================

    'licenseLicensed'        => 'Licenciado',
    'licenseCheckNow'        => 'Verificar Agora',
    'licenseExpired'         => 'Expirado',
    'licenseEnterKey'        => 'Inserir Chave',
    'licenseChangeKey'       => 'Alterar',
    'licenseRenew'           => 'Renovar',
    'licenseThirdParty'      => 'Terceiros',
    'unchecked'              => 'Não Verificado',
    'safetyLabel'            => 'Segurança:',
    'recheckBtn'             => 'Reverificar',
    'recheckSuccess'         => 'Verificação de segurança atualizada.',
    'recheckFailed'          => 'Não foi possível contatar o servidor de verificação. Tente novamente mais tarde.',
    'recheckNotFound'        => 'Item não encontrado.',
    'widgetBlockedMalicious' => '{0} foi marcado como malicioso e não pode ser adicionado.',
    'licenseNoStoreProduct'  => 'Este item não está vinculado a um produto da loja. Se você comprou este item, reinstale-o pelo marketplace para habilitar a licença.',
    'securityWarning'        => 'Aviso de Segurança:',
    'licenseModalTitle'      => 'Inserir Chave de Licença',
    'licenseModalBody'       => 'Cole sua chave de licença abaixo.',
    'licenseModalSave'       => 'Salvar',
    'licenseSaved'           => 'Chave de licença salva e validada.',
    'licenseInvalid'         => 'Chave de licença inválida.',
    'licenseKeyRequired'     => 'Chave de licença e produto são obrigatórios.',
    'licenseCheckFailed'     => 'Não foi possível acessar o servidor de licenças. Tente novamente mais tarde.',
    'licenseProductNotFound' => 'Não foi possível encontrar este item na loja.',
    'btnCancel'              => 'Cancelar',

    // =========================================================================
    // Widgets
    // =========================================================================

    'widgetsTitle'          => 'Widgets',
    'widgetConfigureTitle'  => 'Configurar Widget',
    'widgetAreas'           => 'Áreas de Widgets',
    'widgetAvailable'       => 'Widgets Disponíveis',
    'widgetAddToArea'       => 'Adicionar à Área',
    'widgetArea'            => 'Área',
    'widgetNoOptions'       => 'Sem opções.',
    'widgetSaveConfig'      => 'Salvar Configuração',
    'widgetConfigure'       => 'Configurar',
    'widgetNoAreas'         => 'Nenhuma área de widget encontrada. Ative um tema para habilitar as áreas de widgets.',
    'widgetAreaEmpty'       => 'Nenhum widget nesta área. Adicione um da lista →',

    // Widget flash messages
    'widgetAdded'           => 'Widget adicionado.',
    'widgetRemoved'         => 'Widget removido.',
    'widgetConfigured'      => 'Widget configurado.',

    // =========================================================================
    // Marketplace
    // =========================================================================

    'marketplaceTitle'      => 'Marketplace',
    'marketplaceRefresh'    => 'Atualizar',
    'marketplaceVisitStore' => 'Visitar Loja',
    'marketplaceAll'        => 'Todos',
    'marketplaceThemes'     => 'Temas',
    'marketplaceWidgets'    => 'Widgets',
    'marketplacePlugins'    => 'Plugins',
    'marketplaceUpdatesAvailable' => '{0} atualização(ões) disponível(is).',
    'marketplaceBy'         => 'Por',
    'marketplaceFree'       => 'Gratuito',
    'marketplaceInstalled'  => 'Instalado',
    'marketplaceInstall'    => 'Instalar',
    'marketplaceBuyNow'     => 'Comprar Agora',
    'marketplaceNoItems'    => 'Nenhum item encontrado no marketplace.',
    'marketplaceInstalledVersion' => 'v{0} instalado',
    'marketplaceLoadError'  => 'Não foi possível carregar os produtos da loja. Verifique novamente mais tarde.',
    'byAuthor'              => 'Por {0}',
    'unknown'               => 'Desconhecido',

    // Marketplace flash messages
    'marketplaceInstallSuccess' => '{0} instalado com sucesso.',
    'marketplaceInstallFail'    => 'Falha na instalação. Verifique os logs.',
    'marketplaceUpdateSuccess'  => 'Atualizado com sucesso.',
    'marketplaceUpdateFail'     => 'Falha na atualização.',
    'marketplaceCacheRefreshed' => 'Cache do marketplace atualizado.',
    'marketplaceInvalidRequest' => 'Solicitação de instalação inválida.',
    'marketplaceCannotUpdate'   => 'Não é possível atualizar este item.',

    // =========================================================================
    // Licenses
    // =========================================================================

    // Licenses page
    'licensesTitle'               => 'Licenças',
    'licensesNone'                => 'Sem Licenças',
    'licensesProduct'             => 'Produto',
    'licensesKey'                 => 'Chave de Licença',
    'licensesStatus'              => 'Status',
    'licensesType'                => 'Tipo',
    'licensesExpires'             => 'Expira em',
    'licensesDomain'              => 'Domínio',
    'licensesInstalled'           => 'Instalado',
    'licensesLastChecked'         => 'Última Verificação',
    'licensesActions'             => 'Ações',
    'licensesStatusValid'         => 'Válido',
    'licensesStatusInvalid'       => 'Inválido',
    'licensesStatusExpired'       => 'Expirado',
    'licensesStatusSubExpired'    => 'Assinatura Expirada',
    'licensesStatusUnchecked'     => 'Não Verificado',
    'licensesSubscription'        => 'Assinatura',
    'licensesOneTime'             => 'Única',
    'licensesPerpetual'           => 'Perpétua',
    'licensesNotInstalled'        => 'Não instalado',
    'licensesNever'               => 'Nunca',
    'licensesRevalidate'          => 'Revalidar',
    'licenseKeyPlaceholder'       => 'Inserir chave de licença...',
    'marketplaceLicensesEmpty'    => 'Os produtos licenciados aparecerão aqui após a instalação.',
    'typeTheme'                   => 'Tema',
    'typeWidget'                  => 'Widget',
    'typePlugin'                  => 'Plugin',

    // License revalidation flash messages
    'licenseRevalidateValid'       => 'Licença validada com sucesso.',
    'licenseRevalidateInvalid'     => 'Licença inválida ou expirada.',
    'licenseRevalidateUnreachable' => 'Não foi possível acessar o servidor de licenças. Tente novamente mais tarde.',
    'licenseRevalidateSkipped'     => 'Verificação de licença ignorada (modo de desenvolvimento).',
    'licenseRevalidateNotFound'    => 'Licença não encontrada.',

    // License warning banners
    'licenseWarningTitle'   => 'Problemas com Licença',
    'licenseWarningInvalid' => 'licença inválida ou expirada',
    'licenseWarningManage'  => 'Gerenciar Licenças',

    // Plugin license
    'pluginInvalidLicense' => 'Este plugin possui uma licença inválida ou expirada e não pode ser ativado.',

    // =========================================================================
    // Pubvana Store
    // =========================================================================

    'storeLicenseKey'       => 'Chave de Licença',
    'storeBrowseFull'       => 'Navegar na Loja Completa',
    'storeBackToMarketplace'=> 'Voltar ao Marketplace',
    'storeNoProducts'       => 'Nenhum produto disponível.',
    'storeViewInStore'      => 'Ver na loja',

    // =========================================================================
    // Users
    // =========================================================================

    'usersTitle'            => 'Usuários',
    'editUserTitle'         => 'Editar Usuário',
    'createUserTitle'       => 'Criar Usuário',
    'authorProfileTitle'    => 'Perfil do Autor',
    'userRoleLabel'         => 'Função',
    'userActiveLabel'       => 'Ativo',
    'userPasswordLabel'     => 'Senha',
    'userPasswordOptional'  => 'Deixe em branco para manter a senha atual',
    'userDisplayName'       => 'Nome de Exibição',
    'userBio'               => 'Bio',
    'userWebsite'           => 'Website',
    'userTwitter'           => 'Twitter / X',
    'userFacebook'          => 'Facebook',
    'userLinkedin'          => 'LinkedIn',
    'userAvatar'            => 'Avatar',
    'userSaveProfile'       => 'Salvar Perfil',
    'userSaveChanges'       => 'Salvar Alterações',
    'userCannotDeleteSelf'  => 'Você não pode excluir a si mesmo.',
    'userCannotDeleteOwner' => 'A conta do proprietário do site não pode ser excluída.',
    'userOwnerCannotModify' => 'A conta do proprietário do site não pode ser modificada.',

    // User flash messages
    'userCreated'           => 'Usuário criado.',
    'userUpdated'           => 'Usuário atualizado.',
    'userDeleted'           => 'Usuário excluído.',
    'userBanned'            => 'Usuário banido.',
    'userUnbanned'          => 'Banimento do usuário removido.',
    'userCannotBanSelf'     => 'Você não pode banir a si mesmo ou o proprietário do site.',
    'banStatus'             => 'Status de Banimento',
    'banned'                => 'Banido',
    'ban'                   => 'Banir Usuário',
    'unban'                 => 'Remover Banimento',
    'banReasonRequired'     => 'Um motivo de banimento é obrigatório.',
    'banReasonPlaceholder'  => 'Motivo do banimento...',
    'confirmBanUser'        => 'Tem certeza de que deseja banir este usuário?',
    'userProfileSaved'      => 'Perfil salvo.',
    'userAvatarUploadFail'  => 'Falha no upload do avatar: {0}',

    // =========================================================================
    // Two-Factor Authentication (2FA)
    // =========================================================================

    'tfaSetupTitle'         => 'Configuração 2FA',
    'tfaSetupHeading'       => 'Configurar Autenticação de Dois Fatores',
    'tfaScanQr'             => 'Escaneie o código QR abaixo com seu aplicativo autenticador (ex: Google Authenticator, Authy).',
    'tfaManualEntry'        => 'Ou insira a chave secreta manualmente:',
    'tfaEnterCode'          => 'Insira o código de 6 dígitos do seu aplicativo para confirmar:',
    'tfaCodeLabel'          => 'Código de Autenticação',
    'tfaConfirmBtn'         => 'Confirmar e Habilitar 2FA',
    'tfaDisableBtn'         => 'Desabilitar 2FA',
    'tfaDisableConfirm'     => 'Insira seu código 2FA atual para desabilitar:',
    'tfaEnabled'            => 'Autenticação de dois fatores habilitada.',
    'tfaDisabled'           => 'Autenticação de dois fatores desabilitada.',
    'tfaInvalidCode'        => 'Código inválido - escaneie o código QR novamente e tente uma vez mais.',
    'tfaInvalidDisable'     => 'Código inválido - o 2FA não foi desabilitado.',
    'tfaSessionExpired'     => 'Sessão de configuração expirada - reinicie o processo.',
    'tfaNotEnabled'         => 'O 2FA não está habilitado atualmente.',
    'tfaCantScan'           => "Não consegue escanear? Insira este código manualmente:",
    'tfaWarning'            => 'Guarde esta chave secreta em um local seguro. Você precisará dela para recuperar o acesso se perder seu dispositivo autenticador.',

    // =========================================================================
    // Social Links
    // =========================================================================

    'socialTitle'              => 'Links Sociais',
    'socialPlatform'           => 'Plataforma',
    'socialUrl'                => 'URL',
    'socialIcon'               => 'Ícone',
    'socialSortOrder'          => 'Ordem de Classificação',
    'socialIconPackInfo'       => 'O tema atual <strong>{0}</strong> usa <strong>{1}</strong> (v{2}) para ícones. Abaixo você pode escolher os ícones disponíveis que serão exibidos para o recurso de Links Sociais deste site.',
    'socialSearchPlaceholder'  => 'Buscar plataformas...',
    'socialIconDisclaimer'     => "Esses ícones são apenas uma representação do ícone que será usado. O ícone real pode diferir dependendo do pacote de ícones do tema ativo.",

    // Social flash messages
    'socialLinkAdded'       => 'Link social adicionado.',
    'socialLinkUpdated'     => 'Link atualizado.',
    'socialLinkDeleted'     => 'Link excluído.',

    // =========================================================================
    // Redirects
    // =========================================================================

    'redirectsTitle'        => 'Redirecionamentos',
    'redirectFrom'          => 'URL de Origem',
    'redirectTo'            => 'URL de Destino',
    'redirectType'          => 'Tipo',
    'redirectAdd'           => 'Adicionar Redirecionamento',
    'redirectFromHint'      => '(relativo, ex: /pagina-antiga)',
    'redirect301'           => '301 Permanente',
    'redirect302'           => '302 Temporário',
    'redirectInvalidDest'   => 'URL de destino do redirecionamento inválida.',

    // Redirect flash messages
    'redirectAdded'         => 'Redirecionamento adicionado.',
    'redirectDeleted'       => 'Redirecionamento excluído.',

    // =========================================================================
    // Settings - General
    // =========================================================================

    'settingsTitle'         => 'Configurações',
    'settingsGeneral'       => 'Geral',
    'settingsSeo'           => 'SEO',
    'settingsEmail'         => 'E-mail',
    'settingsSocialLogin'   => 'Login Social',
    'settingsSocialSharing' => 'Compartilhamento Social',
    'settingsSpam'          => 'Proteção contra Spam',

    'generalSettingsHeading'    => 'Configurações Gerais',
    'generalSiteName'           => 'Nome do Site',
    'generalTagline'            => 'Slogan',
    'generalAdminEmail'         => 'E-mail do Administrador',
    'generalPostsPerPage'       => 'Publicações por Página',
    'generalComments'           => 'Comentários',
    'generalCommentsEnable'     => 'Habilitar comentários',
    'generalCommentModeration'  => 'Requerer moderação antes de publicar',
    'generalMaintenanceMode'    => 'Modo de Manutenção',
    'generalMaintenanceEnable'  => 'Habilitar modo de manutenção',
    'generalMaintenanceHelp'    => "Os visitantes veem uma página \"Voltaremos em breve\". Os administradores ainda podem acessar o site.",
    'generalFrontPage'          => 'Página Inicial',
    'generalFrontPageBlog'      => 'Índice do blog (últimas publicações)',
    'generalFrontPageStatic'    => 'Página estática:',
    'generalFrontPagePlugin'    => 'Página de plugin:',
    'generalSelectPage'         => '- Selecionar uma página -',
    'generalSelectRoute'        => '- Selecionar uma rota -',
    'generalFrontPageNoPlugins' => 'Nenhuma rota de plugin disponível',
    'generalPageCacheTtl'       => 'TTL do Cache de Página',
    'settingsCacheTtlHint'      => 'Segundos. 0 = desabilitado.',
    'generalSaveBtn'            => 'Salvar Configurações Gerais',

    // General flash messages
    'generalSettingsSaved'      => 'Configurações gerais salvas.',

    // =========================================================================
    // Settings - SEO
    // =========================================================================

    'seoSettingsHeading'        => 'Configurações de SEO',
    'seoMetaDescription'        => 'Descrição Meta',
    'seoGoogleAnalytics'        => 'ID do Google Analytics',
    'seoGoogleAnalyticsPlaceholder' => 'G-XXXXXXXXXX',
    'seoSitemap'                => 'Mapa do Site',
    'seoSitemapEnable'          => 'Habilitar sitemap.xml',
    'seoSitemapHelp'            => 'Mapa do site padrão para todas as publicações e páginas publicadas.',
    'seoNewsSitemap'            => 'Habilitar news-sitemap.xml',
    'seoNewsSitemapHelp'        => 'Mapa do site do Google News - lista publicações das últimas 48 horas.',
    'seoSaveBtn'                => 'Salvar Configurações de SEO',
    'seoSettingsSaved'          => 'Configurações de SEO salvas.',

    // =========================================================================
    // Settings - Email
    // =========================================================================

    'emailSettingsHeading'      => 'Configurações de E-mail',
    'emailFromName'             => 'Nome do Remetente',
    'emailFromAddress'          => 'Endereço do Remetente',
    'emailProtocol'             => 'Protocolo',
    'emailProtocolMail'         => 'PHP Mail',
    'emailProtocolSmtp'         => 'SMTP',
    'emailProtocolSendmail'     => 'Sendmail',
    'emailSmtpHost'             => 'Host SMTP',
    'emailSmtpPort'             => 'Porta SMTP',
    'emailSmtpEncryption'       => 'Criptografia',
    'emailSmtpEncryptionNone'   => 'Nenhuma',
    'emailSmtpUsername'         => 'Usuário SMTP',
    'emailSmtpPassword'         => 'Senha SMTP',
    'emailSaveBtn'              => 'Salvar Configurações de E-mail',
    'emailSettingsSaved'        => 'Configurações de e-mail salvas.',

    // =========================================================================
    // Settings - Social Login (OAuth)
    // =========================================================================

    'socialLoginHeading'        => 'Login Social (OAuth)',
    'socialLoginHelp'           => 'As credenciais são salvas no seu arquivo .env. Registre seu aplicativo no Google e no Facebook para obter IDs de cliente e segredos.',
    'socialLoginGoogleId'       => 'ID do Cliente',
    'socialLoginGoogleSecret'   => 'Segredo do Cliente',
    'socialLoginFbAppId'        => 'ID do Aplicativo',
    'socialLoginFbAppSecret'    => 'Segredo do Aplicativo',
    'socialLoginPlaceholderSecret' => '(deixe em branco para manter o existente)',
    'socialLoginSaveBtn'        => 'Salvar Configurações de Login Social',
    'socialLoginSettingsSaved'  => 'Configurações de login social salvas.',

    // =========================================================================
    // Settings - Social Sharing
    // =========================================================================

    'socialSharingHeading'      => 'Compartilhamento Social Automático ao Publicar',
    'socialSharingHelp'         => 'Quando uma publicação é publicada com "Compartilhar ao publicar" marcado, o Pubvana publicará automaticamente nas contas sociais configuradas.',
    'socialSharingTwitter'      => 'Twitter / X',
    'socialSharingTwitterHelp'  => 'Obtenha as chaves em developer.twitter.com → Seu App → Chaves e Tokens.',
    'socialSharingApiKey'       => 'Chave de API',
    'socialSharingApiSecret'    => 'Segredo da API',
    'socialSharingAccessToken'  => 'Token de Acesso',
    'socialSharingAccessSecret' => 'Segredo de Acesso',
    'socialSharingFbPage'       => 'Página do Facebook',
    'socialSharingFbPageHelp'   => 'Requer um Token de Acesso de Página com permissão pages_manage_posts.',
    'socialSharingFbPageId'     => 'ID da Página',
    'socialSharingFbPageToken'  => 'Token de Acesso da Página',
    'socialSharingSaveBtn'      => 'Salvar Configurações de Compartilhamento',
    'socialSharingSettingsSaved'=> 'Configurações de compartilhamento social salvas.',

    // =========================================================================
    // Settings - Spam Protection (hCaptcha)
    // =========================================================================

    'spamProtectionHeading'     => 'Proteção contra Spam (hCaptcha)',
    'spamHcaptchaIntro'         => 'O Pubvana usa o hCaptcha (privacidade respeitada, não Google) para proteger formulários de comentários e de contato contra bots de spam.',
    'spamHcaptchaFree'          => 'O hCaptcha é gratuito para a maioria dos sites. Cadastre-se em hcaptcha.com, crie um site e insira suas chaves abaixo.',
    'spamHcaptchaSiteKey'       => 'Chave do Site',
    'spamHcaptchaSecretKey'     => 'Chave Secreta',
    'spamHcaptchaNote'          => 'Se essas chaves não estiverem configuradas, o hCaptcha é silenciosamente ignorado — seguro para desenvolvimento local. Uma vez salvo, o widget aparece automaticamente no formulário de comentários e na página de contato.',
    'spamSettingsSaved'         => 'Configurações de proteção contra spam salvas.',

    // =========================================================================
    // Languages
    // =========================================================================

    'languagesTitle'            => 'Idiomas',
    'languageCode'              => 'Código',
    'languageName'              => 'Nome',
    'languageDefault'           => 'Padrão',
    'languageEnabled'           => 'Habilitado',
    'languageMakeDefault'       => 'Definir como Padrão',
    'languageSetAsDefault'      => '{0} definido como idioma padrão.',
    'languageEnabled_msg'       => '{0} habilitado.',
    'languageDisabled_msg'      => '{0} desabilitado.',
    'languageNotFound'          => 'Idioma não encontrado.',
    'languageCannotDisable'     => 'Não é possível desabilitar o idioma padrão.',
    'languageDirection'         => 'Direção',
    'languageNativeName'        => 'Nome Nativo',

    // =========================================================================
    // Analytics
    // =========================================================================

    'analyticsTitle'            => 'Análises',
    'analyticsTotalViews'       => 'Total de Visualizações',
    'analyticsTopPosts'         => 'Publicações Mais Vistas',
    'analyticsReferrers'        => 'Principais Referenciadores',
    'analyticsLast7'            => 'Últimos 7 dias',
    'analyticsLast30'           => 'Últimos 30 dias',
    'analyticsLast90'           => 'Últimos 90 dias',
    'analyticsChartTitle'       => 'Visualizações de Página',
    'analyticsNoData'           => 'Sem dados analíticos para este período.',
    'analyticsDomain'           => 'Domínio',

    // =========================================================================
    // Affiliate Links
    // =========================================================================

    'affiliatesTitle'           => 'Links de Afiliados',
    'newAffiliateLinkTitle'     => 'Novo Link de Afiliado',
    'editAffiliateLinkTitle'    => 'Editar Link de Afiliado',
    'affiliateName'             => 'Nome',
    'affiliateSlug'             => 'Slug',
    'affiliateDestination'      => 'URL de Destino',
    'affiliateActive'           => 'Ativo',
    'affiliateClicks'           => 'Cliques',
    'affiliateClicksTitle'      => 'Cliques - {0}',
    'affiliateTotal'            => 'Total',
    'affiliateViewClicks'       => 'Ver Cliques',

    // Affiliate flash messages
    'affiliateCreated'          => 'Link de afiliado criado.',
    'affiliateUpdated'          => 'Link de afiliado atualizado.',
    'affiliateDeleted'          => 'Link de afiliado excluído.',

    // =========================================================================
    // Broken Links
    // =========================================================================

    'brokenLinksTitle'          => 'Links Quebrados',
    'brokenLinkUrl'             => 'URL',
    'brokenLinkStatus'          => 'Status HTTP',
    'brokenLinkError'           => 'Erro',
    'brokenLinkSource'          => 'Fonte',
    'brokenLinkShowDismissed'   => 'Mostrar dispensados',
    'brokenLinkHideDismissed'   => 'Ocultar dispensados',
    'brokenLinkTimeout'         => 'Tempo esgotado',
    'brokenLinkBroken'          => 'quebrado',
    'brokenLinkNone'            => 'Nenhum link quebrado detectado.',
    'brokenLinkNowReachable'    => 'Link agora acessível - removido dos resultados.',
    'brokenLinkStillBroken'     => 'Link ainda quebrado ({0}).',
    'brokenLinkDismissed'       => 'Link dispensado.',
    'brokenLinksCliHint'        => 'Execute uma verificação completa pela linha de comando para popular este relatório: <code class="ml-1">php spark links:check</code>',
    'brokenLinksIssueCount'     => '{0} problema(s) encontrado(s)',
    'brokenLinksCount'          => '{0} quebrado(s)',
    'brokenLinksRecheck'        => 'Verificar novamente esta URL',
    'brokenLinksDismiss'        => 'Dispensar (ocultar dos resultados)',
    'brokenLinksRunScan'        => 'Executar Verificação',
    'brokenLinksScanComplete'   => 'Verificação concluída: {0} links verificados, {1} quebrados.',
    'timeout'                   => 'Tempo esgotado',
    'typePost'                  => 'Publicação',
    'typePage'                  => 'Página',

    // =========================================================================
    // Activity Log
    // =========================================================================

    'activityLogTitle'          => 'Registro de Atividades',
    'activityLogType'           => 'Tipo',
    'activityLogAction'         => 'Ação',
    'activityLogUser'           => 'Usuário',
    'activityLogDate'           => 'Data',
    'activityLogNote'           => 'Observação',
    'activityLogFilterAll'      => 'Todos os Tipos',
    'activityLogEmpty'          => 'Nenhuma atividade registrada ainda.',

    // =========================================================================
    // Backup & Export
    // =========================================================================

    'backupTitle'               => 'Backup e Exportação',
    'backupDownload'            => 'Criar e Baixar Backup',
    'backupFiles'               => 'Backups Disponíveis',
    'backupFilename'            => 'Nome do Arquivo',
    'backupSize'                => 'Tamanho',
    'backupDate'                => 'Criado em',
    'backupGenerating'          => 'Gerando backup…',
    'backupNoFiles'             => 'Nenhum backup salvo.',
    'backupFailed'              => 'Falha no backup: {0}',
    'backupDeleted'             => 'Backup excluído.',
    'backupCannotDelete'        => 'Não foi possível excluir o backup.',
    // ─── Affiliates, Activity Log, Analytics, Comments ──
    'colIp'                     => 'IP',
    'affiliateIpHashNote'       => 'Os IPs são armazenados como hashes SHA-256 — nenhum dado pessoal bruto é registrado.',
    'colTime'                   => 'Hora',
    'colIpHash'                 => 'Hash de IP',
    'colReferrer'               => 'Referenciador',
    'affiliateDirectReferrer'   => 'Direto',
    'affiliateNameHint'         => 'Rótulo interno — não mostrado aos visitantes.',
    'affiliateSlugHint'         => 'Apenas letras, números, hífens e underscores. Não pode ser alterado depois que os links forem compartilhados.',
    'affiliateDestHint'         => 'Deve incluir https://. Os visitantes serão redirecionados 301 para cá.',
    'affiliateInactiveHint'     => 'Links inativos retornam 404.',
    'affiliateLinkCount'        => '{0} Links',
    'colDomain'                 => 'Domínio',
    'commentAll'                => 'Todos',
    'commentPending'            => 'Pendente',
    'commentTrash'              => 'Lixeira',
    'commentsNone'              => 'Nenhum comentário {0}.',

    // ─── Backups & Import ────────────────────
    'backupCreate'              => 'Criar Backup',
    'backupStarting'            => 'Iniciando backup...',
    'backupNoneYet'             => 'Nenhum backup ainda. Clique em "Criar Backup" para criar o seu primeiro.',
    'backupsTitle'              => 'Backups',
    'backupRetentionNote'       => 'Máximo de 15 backups retidos — os mais antigos são excluídos automaticamente.',
    'backupRestoreConfirm'      => 'Restaurar este backup? Um backup do estado atual será criado primeiro.',
    'backupDeleteConfirm'       => 'Excluir este backup?',
    'colFilename'               => 'Nome do Arquivo',
    'colVersion'                => 'Versão',
    'colTrigger'                => 'Gatilho',
    'colSize'                   => 'Tamanho',
    'colDate'                   => 'Data',
    'colActions'                => 'Ações',

    // =========================================================================
    // WordPress Import
    // =========================================================================

    'importTitle'               => 'Importar',
    'importWpHeading'           => 'Importar do WordPress',
    'importWpHelp'              => 'Exporte seu site WordPress via Ferramentas → Exportar, depois faça upload do arquivo .xml abaixo.',
    'importChooseFile'          => 'Escolher Arquivo WXR (.xml)',
    'importDryRun'              => 'Execução de teste (somente pré-visualização - nada é salvo)',
    'importRunBtn'              => 'Executar Importação',
    'importNoValidFile'         => 'Por favor, faça upload de um arquivo de exportação WXR válido do WordPress.',
    'importOnlyXml'             => 'Somente arquivos .xml são aceitos.',
    'importFileTooLarge'        => 'Arquivo de importação muito grande. O tamanho máximo é 50 MB.',
    'importResultsHeading'      => 'Resultados da Importação',
    'importDryRunNote'          => 'Execução de teste - nenhum dado foi salvo.',
    'importDryRunLabel'         => '(Execução de Teste — nenhum dado gravado)',
    'importComplete'            => 'Importação Concluída',
    'importCreated'             => 'criado',
    'importSkipped'             => 'ignorado',
    'importErrors'              => 'Erros:',
    'importInstructions'        => 'Exporte seu conteúdo do WordPress em <strong>Ferramentas → Exportar → Todo o conteúdo</strong> e faça upload do arquivo <code>.xml</code> aqui. O Pubvana importará publicações, páginas, categorias, tags, autores e comentários.',
    'importCliTitle'            => 'Importação via CLI',
    'importCliHint'             => 'Você também pode executar o importador pela linha de comando:',
    'importCliDryRunHint'       => 'O sinalizador <code>--dry-run</code> mostra o que seria importado sem gravar no banco de dados.',
    'importWhatTitle'           => 'O que é Importado',
    'importItemPosts'           => 'Publicações (título, conteúdo, resumo, slug, status)',
    'importItemPages'           => 'Páginas',
    'importItemCategories'      => 'Categorias (com hierarquia)',
    'importItemTags'            => 'Tags',
    'importItemAuthors'         => 'Autores (criados como contas de assinante)',
    'importItemComments'        => 'Comentários',
    'importItemMedia'           => 'Arquivos de mídia (URLs preservadas no conteúdo)',

    // =========================================================================
    // Updates
    // =========================================================================

    'updatesTitle'              => 'Atualizações',
    'updatesCurrentVersion'     => 'Versão Atual',
    'updatesLatestVersion'      => 'Versão Mais Recente',
    'updatesUpToDate'           => 'Pubvana está atualizado.',
    'updatesAvailable'          => 'Atualização disponível: {0}',
    'updatesCheckBtn'           => 'Verificar Atualizações',
    'updatesReleaseNotes'       => 'Notas de Versão',
    'updatesHowToApply'         => 'Como Aplicar uma Atualização',
    'updatesCacheCleared'       => 'Cache de atualização limpo - verificando novamente.',
    'updatesExtCapped'          => 'Atualização disponível: {0} (seguro para complementos)',
    'updatesNewerAvailable'     => 'Pubvana {0} também está disponível - atualize os complementos listados abaixo para desbloqueá-lo.',

    // Addon Updates
    'updatesExtTitle'               => 'Complementos',
    'updatesExtCheckAll'            => 'Verificar Todos',
    'updatesExtUpdateAll'           => 'Atualizar Todos',
    'updatesExtCheckAllType'        => 'Verificar Todos {0}',
    'updatesExtUpdateAllType'       => 'Atualizar Todos {0}',
    'updatesExtNoInstalled'         => 'Nenhum {0} instalado.',
    'updatesExtColName'             => 'Nome',
    'updatesExtColVersion'          => 'Versão',
    'updatesExtColLatest'           => 'Mais Recente',
    'updatesExtColAutoUpdate'       => 'Atualização Automática',
    'updatesExtColStatus'           => 'Status',
    'updatesExtColActions'          => 'Ações',
    'updatesExtBundled'             => 'Incluído no Core',
    'updatesExtNoSource'            => 'Sem fonte de atualização',
    'updatesExtFailed'              => 'Falhou',
    'updatesExtUpdatedAt'           => 'Atualizado em {0}',
    'updatesExtAvailable'           => 'Atualização disponível',
    'updatesExtUpToDate'            => 'Atualizado',
    'updatesExtUpdate'              => 'Atualizar',
    'updatesExtChecking'            => 'Verificando...',
    'updatesExtUpdating'            => 'Atualizando...',
    'updatesExtUpdated'             => 'Atualizado',

    // CMS Update Confirmation Modal
    'updatesConfirmTitle'           => 'Confirmar Atualização',
    'updatesConfirmBody'            => 'Isso fará backup do seu site, baixará a atualização e a aplicará.',
    'updatesConfirmSafe'            => 'Seu <code>.env</code>, <code>App.php</code> e <code>Database.php</code> nunca são sobrescritos.',
    'updatesConfirmBtn'             => 'Atualizar Agora',

    // Addon Update All Modal
    'updatesExtAllTitle'            => 'Atualizar Todos os Complementos',
    'updatesExtAllBody'             => 'Isso atualizará todos os complementos que possuem atualizações pendentes.',
    'updatesExtAllNote'             => 'Complementos com atualização automática desabilitada também serão atualizados.',
    'updatesExtAllBtn'              => 'Atualizar Todos',

    'updatesExtBadge'               => 'Atualização: v{0}',
    'updatesExtGoToUpdates'         => 'Atualizações',

    // Update Settings
    'updatesSettingsTitle'          => 'Configurações de Atualização',
    'updatesAutoUpdateLabel'        => 'Atualização Automática do Pubvana',
    'updatesAutoUpdateManual'       => 'Manual',
    'updatesAutoUpdateAuto'         => 'Automático',
    'updatesAutoUpdateHelp'         => 'Quando habilitado, as atualizações do Pubvana sem alterações significativas são aplicadas automaticamente.',
    'updatesCheckMethodLabel'       => 'Método de Verificação de Atualização',
    'updatesCheckMethodPageload'    => 'Carregamento de Página',
    'updatesCheckMethodCron'        => 'Cron Job',
    'updatesCheckMethodHelp'        => 'O Carregamento de Página verifica em cada requisição (cache de 24h). O Cron requer um cron job no servidor.',
    'updatesCronCommand'            => 'Comando Cron',
    'updatesCronHelp'               => 'Adicione isso ao crontab do seu servidor para executar a verificação de atualização diariamente:',
    'updatesSettingsSaved'          => 'Configurações de atualização salvas.',

    // Compatibility
    'compatWarningTitle'            => 'Aviso de Compatibilidade',
    'compatNotCompatible'           => 'Alguns complementos instalados não são compatíveis com esta versão.',
    'compatRequiresUpdate'          => 'mas requer que os seguintes complementos sejam atualizados primeiro:',
    'compatSupportsUpTo'            => 'suporta até {0}',
    'compatRequiresMin'             => 'requer Pubvana {0}+',
    'compatNotDeclared'             => 'Os seguintes complementos não declararam compatibilidade com o Pubvana {0}. Eles podem parar de funcionar após a atualização:',
    'compatColType'                 => 'Tipo',
    'compatColName'                 => 'Nome',
    'compatColVersion'              => 'Compatibilidade',
    'compatRemoveHint'              => 'Você pode remover complementos incompatíveis ou mudar para o tema padrão se ocorrerem problemas. Um backup é criado antes de cada atualização.',
    'compatMaxVersion'              => 'Versão máxima compatível: {0}',
    'compatMinVersion'              => 'Requer Pubvana {0}+',

    // =========================================================================
    // Schedule
    // =========================================================================

    'scheduleTitle'             => 'Agendamento de Publicações',
    'scheduleNoScheduled'       => 'Nenhuma publicação agendada.',

    // =========================================================================
    // Post Revisions page header
    // =========================================================================

    'revisionsPageTitle'        => 'Revisões - {0}',
    'revisionPageTitle'         => 'Revisão - {0}',

    // =========================================================================
    // Admin access / gate messages
    // =========================================================================

    'adminLoginRequired'        => 'Você deve estar logado para acessar o painel administrativo.',
    'dirNotWritable'            => 'O diretório não tem permissão de escrita: {0}',

    // =========================================================================
    // Addon Licensing & Notifications
    // =========================================================================

    // admin_notifications (persistent)
    'addonMisconfigured'        => '{0} está configurado incorretamente. Se você é o usuário final, entre em contato com o desenvolvedor. Se você é o desenvolvedor, consulte a documentação.',
    'addonMisconfiguredLink'    => '{0} está configurado incorretamente. Se você é o usuário final <a href="{1}">entre em contato com o desenvolvedor</a>. Se você é o desenvolvedor <a href="https://github.com/enlivenapp/pubvana">consulte a documentação</a>.',
    'licenseExpiringSoon'       => 'A licença de {0} expira em {1}. {0} será desativado quando a licença expirar.',
    'licenseExpiredDeactivated' => '{0} foi desativado porque a licença expirou.',
    'addonDeactivated'          => '{0} foi desativado. Motivo: {1}.',
    'widgetValidationFailed'    => "O widget ''{0}'' não pôde ser validado. Entre em contato com o desenvolvedor ou remova o complemento.",
    'widgetValidationFailedLink' => "O widget ''{0}'' não pôde ser validado. <a href=\"{1}\">Entre em contato com o desenvolvedor</a> ou remova o complemento.",

    // Inline warnings on addon listing (deactivated reasons)
    'addonDeactivatedExpired'   => 'Desativado: licença expirada',
    'addonDeactivatedTampered'  => 'Desativado: configurado incorretamente',
    'addonDeactivatedNoLicense' => 'Desativado: sem licença válida',

    // Disabled addon reasons (system-set)
    'addonDisabled'             => 'Desabilitado',
    'addonDisabledInvalidJson'  => 'Sistema: {0} possui um {1} inválido ou ilegível.',
    'addonDisabledMissingFields' => 'Sistema: {0} está faltando campos obrigatórios: {1}.',
    'addonDisabledPhpFiles'     => 'Sistema: {0} contém arquivos PHP. Widgets devem ser somente JSON + templates.',

    // Flash messages (on activation attempt)
    'licenseRequired'           => 'Uma licença válida é necessária para ativar {0}.',
    'licenseInvalidActivation'  => 'Falha na validação da licença para {0}. Por favor, verifique sua chave de licença.',
    'licenseExpiredActivation'  => 'A licença de {0} expirou. Renove para ativar.',
    'licenseCheckUnreachable'   => 'Não foi possível verificar a licença de {0}. O servidor de licenças está inacessível. Tente novamente mais tarde.',
    'activationBlockedTampered' => '{0} não pode ser ativado porque está configurado incorretamente.',
    'activationBlockedBundled'  => '{0} não pode ser ativado: somente complementos Pubvana podem ser marcados como incluídos.',
    'activationBlockedNoUrls'   => '{0} não pode ser ativado: complementos pagos devem incluir URLs de verificação de licença.',
    'activationBlockedFreeFlag' => '{0} não pode ser ativado: complementos Pubvana não podem ser marcados como gratuitos.',
    'activationBlockedDisabled' => '{0} não pode ser ativado porque possui erros de configuração. Verifique o arquivo de informações.',

    // Third-party license
    'licenseThirdPartyLabel'    => 'Terceiros',

    // =========================================================================
    // Updates page — progress, alerts, pre-flight, buttons
    // =========================================================================

    'updateStarting'             => 'Iniciando atualização...',
    'updateCheckLabel'           => 'Verificação de atualização:',
    'updateAvailable'            => 'Pubvana {0} está disponível!',
    'updateRunning'              => 'Você está executando {0}.',
    'updateBreakingChanges'      => 'Alterações Significativas',
    'updateMigrationNotes'       => 'Notas de Migração',
    'updateNotices'              => 'Avisos',
    'updatePreflightTitle'       => 'Verificações Pré-voo',
    'updateToVersion'            => 'Atualizar para Pubvana {0}',
    'updatePreflightFailed'      => 'Uma ou mais verificações pré-voo obrigatórias falharam. Resolva-as antes de atualizar.',
    'updateUpToDate'             => 'Pubvana está atualizado. Você está executando a versão {0}.',
    'updateAnyway'               => 'Atualizar Mesmo Assim',
    'updateAvailableTooltip'     => 'Pubvana {0} disponível',

    // =========================================================================
    // Users — index page
    // =========================================================================

    'youLabel'                   => '(você)',
    'usersNone'                  => 'Nenhum usuário encontrado.',

    // =========================================================================
    // Users — edit page
    // =========================================================================

    'accountActive'              => 'Conta ativa',

    // =========================================================================
    // Users — profile page
    // =========================================================================

    'profileDetails'             => 'Detalhes do Perfil',
    'profileDisplayNameHint'     => 'Exibido nas publicações publicadas em vez do nome de usuário.',
    'profileAvatarHint'          => 'JPEG, PNG, WebP ou GIF. Máx 10 MB.',
    'profileSocialHandles'       => 'Redes Sociais',
    'preview'                    => 'Pré-visualizar',
    'website'                    => 'Website',

    // =========================================================================
    // Two-Factor Authentication — profile card & setup page
    // =========================================================================

    'twoFactorTitle'             => 'Autenticação de Dois Fatores',
    'totpActiveDesc'             => 'A autenticação de dois fatores TOTP está ativa em sua conta. Você será solicitado a inserir um código de 6 dígitos do seu aplicativo autenticador a cada login.',
    'totpCurrentCode'            => 'Código Atual',
    'totpInactiveDesc'           => 'Adicione uma camada extra de segurança à sua conta. Uma vez habilitado, você precisará inserir um código do seu aplicativo autenticador a cada login.',
    'totpEnable'                 => 'Habilitar Autenticação de Dois Fatores',
    'totpScanInstructions'       => 'Abra seu aplicativo autenticador (Google Authenticator, Authy, 1Password, etc.) e escaneie este código QR.',
    'totpManualEntry'            => "Não consegue escanear? Insira este código manualmente:",
    'totpConfirmInstructions'    => 'Após escanear, insira o código de 6 dígitos exibido no seu aplicativo para confirmar a configuração.',
    'totpRecoveryWarning'        => 'Guarde seus códigos de recuperação. Se você perder o acesso ao seu aplicativo autenticador, não poderá fazer login. Entre em contato com o administrador do site para redefinir o 2FA.',

];
