-- Migración: Sistema de Chat en Vivo con WebSockets
-- Fecha: 2025-12-21

-- 1. Tabla de mensajes de chat
CREATE TABLE IF NOT EXISTS chat_messages (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER NOT NULL,
    receiver_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Índices para optimizar consultas
CREATE INDEX IF NOT EXISTS idx_chat_messages_sender ON chat_messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_receiver ON chat_messages(receiver_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_created ON chat_messages(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_chat_messages_conversation ON chat_messages(sender_id, receiver_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_unread ON chat_messages(receiver_id, is_read) WHERE is_read = FALSE;

-- 2. Tabla de conversaciones (para listar chats activos)
CREATE TABLE IF NOT EXISTS chat_conversations (
    id SERIAL PRIMARY KEY,
    user1_id INTEGER NOT NULL,
    user2_id INTEGER NOT NULL,
    last_message_id INTEGER,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (last_message_id) REFERENCES chat_messages(id) ON DELETE SET NULL,
    UNIQUE(user1_id, user2_id),
    CHECK (user1_id < user2_id) -- Asegurar que user1 siempre sea menor
);

CREATE INDEX IF NOT EXISTS idx_chat_conversations_user1 ON chat_conversations(user1_id);
CREATE INDEX IF NOT EXISTS idx_chat_conversations_user2 ON chat_conversations(user2_id);
CREATE INDEX IF NOT EXISTS idx_chat_conversations_last_message ON chat_conversations(last_message_at DESC);

-- 3. Tabla de conexiones activas (para tracking de usuarios online)
CREATE TABLE IF NOT EXISTS chat_connections (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    connection_id VARCHAR(100) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_chat_connections_user ON chat_connections(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_connections_last_ping ON chat_connections(last_ping);

-- 4. Tabla de notificaciones de chat
CREATE TABLE IF NOT EXISTS chat_notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    sender_id INTEGER NOT NULL,
    message_id INTEGER NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_chat_notifications_user ON chat_notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_notifications_unread ON chat_notifications(user_id, is_read) WHERE is_read = FALSE;

-- 5. Función para obtener conversaciones de un usuario
CREATE OR REPLACE FUNCTION get_user_conversations(p_user_id INTEGER)
RETURNS TABLE (
    conversation_id INTEGER,
    other_user_id INTEGER,
    other_username VARCHAR(100),
    last_message TEXT,
    last_message_at TIMESTAMP,
    unread_count BIGINT,
    is_online BOOLEAN
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        c.id as conversation_id,
        CASE 
            WHEN c.user1_id = p_user_id THEN c.user2_id
            ELSE c.user1_id
        END as other_user_id,
        u.username as other_username,
        m.message as last_message,
        c.last_message_at,
        (
            SELECT COUNT(*)
            FROM chat_messages cm
            WHERE cm.receiver_id = p_user_id
            AND cm.sender_id = CASE 
                WHEN c.user1_id = p_user_id THEN c.user2_id
                ELSE c.user1_id
            END
            AND cm.is_read = FALSE
        ) as unread_count,
        EXISTS(
            SELECT 1 FROM chat_connections cc
            WHERE cc.user_id = CASE 
                WHEN c.user1_id = p_user_id THEN c.user2_id
                ELSE c.user1_id
            END
            AND cc.last_ping > NOW() - INTERVAL '30 seconds'
        ) as is_online
    FROM chat_conversations c
    LEFT JOIN chat_messages m ON c.last_message_id = m.id
    LEFT JOIN users u ON u.id = CASE 
        WHEN c.user1_id = p_user_id THEN c.user2_id
        ELSE c.user1_id
    END
    WHERE c.user1_id = p_user_id OR c.user2_id = p_user_id
    ORDER BY c.last_message_at DESC;
END;
$$ LANGUAGE plpgsql;

-- 6. Función para marcar mensajes como leídos
CREATE OR REPLACE FUNCTION mark_messages_as_read(
    p_receiver_id INTEGER,
    p_sender_id INTEGER
) RETURNS INTEGER AS $$
DECLARE
    affected_rows INTEGER;
BEGIN
    UPDATE chat_messages
    SET is_read = TRUE, read_at = CURRENT_TIMESTAMP
    WHERE receiver_id = p_receiver_id 
    AND sender_id = p_sender_id 
    AND is_read = FALSE;
    
    GET DIAGNOSTICS affected_rows = ROW_COUNT;
    
    -- Marcar notificaciones como leídas
    UPDATE chat_notifications
    SET is_read = TRUE
    WHERE user_id = p_receiver_id 
    AND sender_id = p_sender_id 
    AND is_read = FALSE;
    
    RETURN affected_rows;
END;
$$ LANGUAGE plpgsql;

-- 7. Función para limpiar conexiones inactivas (más de 5 minutos sin ping)
CREATE OR REPLACE FUNCTION cleanup_inactive_connections()
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM chat_connections
    WHERE last_ping < NOW() - INTERVAL '5 minutes';
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

-- 8. Trigger para actualizar conversación cuando se envía un mensaje
CREATE OR REPLACE FUNCTION update_conversation_on_message()
RETURNS TRIGGER AS $$
DECLARE
    v_user1_id INTEGER;
    v_user2_id INTEGER;
    v_conversation_id INTEGER;
BEGIN
    -- Determinar user1 y user2 (user1 siempre menor)
    IF NEW.sender_id < NEW.receiver_id THEN
        v_user1_id := NEW.sender_id;
        v_user2_id := NEW.receiver_id;
    ELSE
        v_user1_id := NEW.receiver_id;
        v_user2_id := NEW.sender_id;
    END IF;
    
    -- Insertar o actualizar conversación
    INSERT INTO chat_conversations (user1_id, user2_id, last_message_id, last_message_at)
    VALUES (v_user1_id, v_user2_id, NEW.id, NEW.created_at)
    ON CONFLICT (user1_id, user2_id) 
    DO UPDATE SET 
        last_message_id = NEW.id,
        last_message_at = NEW.created_at
    RETURNING id INTO v_conversation_id;
    
    -- Crear notificación para el receptor
    INSERT INTO chat_notifications (user_id, sender_id, message_id)
    VALUES (NEW.receiver_id, NEW.sender_id, NEW.id);
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_update_conversation_on_message
AFTER INSERT ON chat_messages
FOR EACH ROW
EXECUTE FUNCTION update_conversation_on_message();

-- Comentarios
COMMENT ON TABLE chat_messages IS 'Mensajes de chat entre usuarios';
COMMENT ON TABLE chat_conversations IS 'Conversaciones activas entre usuarios';
COMMENT ON TABLE chat_connections IS 'Conexiones WebSocket activas';
COMMENT ON TABLE chat_notifications IS 'Notificaciones de mensajes no leídos';
COMMENT ON FUNCTION get_user_conversations IS 'Obtiene lista de conversaciones con contadores de mensajes no leídos';
COMMENT ON FUNCTION mark_messages_as_read IS 'Marca mensajes de una conversación como leídos';
COMMENT ON FUNCTION cleanup_inactive_connections IS 'Limpia conexiones WebSocket inactivas';
