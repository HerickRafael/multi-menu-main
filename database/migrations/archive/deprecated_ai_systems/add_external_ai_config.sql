-- ============================================================================
-- Migration: Adicionar Configuração de IA Externa
-- ============================================================================
-- Permite integração com APIs externas (OpenAI, Gemini) para acelerar
-- treinamento da IA interna
-- ============================================================================

ALTER TABLE cross_sell_config
ADD COLUMN ai_provider ENUM('local', 'openai', 'gemini', 'custom') DEFAULT 'local' COMMENT 'Provider de IA: local=algoritmo próprio, openai=ChatGPT, gemini=Google, custom=endpoint customizado',
ADD COLUMN api_key VARCHAR(255) DEFAULT NULL COMMENT 'Chave de API do provider externo (criptografada)',
ADD COLUMN api_endpoint VARCHAR(500) DEFAULT NULL COMMENT 'Endpoint customizado (para provider=custom)',
ADD COLUMN hybrid_mode TINYINT(1) DEFAULT 0 COMMENT 'Modo híbrido: usa IA externa para treinar a interna',
ADD COLUMN last_external_training DATETIME DEFAULT NULL COMMENT 'Última vez que rodou treinamento com IA externa',
ADD COLUMN external_training_status ENUM('idle', 'running', 'completed', 'failed') DEFAULT 'idle' COMMENT 'Status do treinamento externo';

-- Adicionar índice para buscar empresas em modo híbrido
ALTER TABLE cross_sell_config
ADD INDEX idx_hybrid_mode (hybrid_mode, ai_provider);

-- Comentário explicativo
ALTER TABLE cross_sell_config
COMMENT = 'Configuração de cross-sell com suporte a IA local e externa para aprendizado híbrido';
