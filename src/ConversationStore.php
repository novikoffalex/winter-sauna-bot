<?php

class ConversationStore
{
    private $dir;
    private $maxMessages;

    public function __construct($dir = 'data/conversations', $maxMessages = 20)
    {
        $this->dir = $dir;
        $this->maxMessages = $maxMessages;
        if (!file_exists($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    public function load($chatId)
    {
        $file = $this->getFile($chatId);
        if (!file_exists($file)) {
            return [];
        }
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    public function save($chatId, $messages)
    {
        // Обрезаем историю до лимита
        if (count($messages) > $this->maxMessages) {
            $messages = array_slice($messages, -$this->maxMessages);
        }
        file_put_contents($this->getFile($chatId), json_encode($messages, JSON_UNESCAPED_UNICODE));
    }

    public function append($chatId, $role, $content)
    {
        $messages = $this->load($chatId);
        $messages[] = [ 'role' => $role, 'content' => $content, 'ts' => time() ];
        $this->save($chatId, $messages);
        return $messages;
    }

    private function getFile($chatId)
    {
        return $this->dir . '/chat_' . $chatId . '.json';
    }
}


