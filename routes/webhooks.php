<?php
/* ========= Webhooks Evolution API ========= */
$router->post('/webhook/evolution/{instanceName}', 'WebhookEvolutionController@messages')->name('webhook.evolution.messages');
$router->post('/webhook/evolution-worker',         'WebhookEvolutionController@processQueue')->name('webhook.evolution.worker');
$router->get('/webhook/evolution-queue-stats',      'WebhookEvolutionController@queueStats')->name('webhook.evolution.queue-stats');
$router->post('/webhook/evolution-dlq-retry',       'WebhookEvolutionController@dlqRetry')->name('webhook.evolution.dlq-retry');

/* ========= Webhooks iFood ========= */
$router->post('/webhook/ifood', 'WebhookIFoodController@handle')->name('webhook.ifood');

