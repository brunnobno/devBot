<?php
// GitHub.php

require_once __DIR__ . '/config.php';

class GitHub {
    private $apiBase = 'https://api.github.com/repos/';
    private $user;
    private $repo;

    public function __construct() {
        $this->user = GITHUB_USER;
        $this->repo = $_GET['repo'] ?? $_POST['repo'] ?? GITHUB_REPO;
    }

    public function getFile($path) {
        $url = $this->buildUrl($path);
        return $this->request('GET', $url);
    }

    public function updateFile($path, $content, $sha, $message) {
        $url = $this->buildUrl($path);
        $body = json_encode([
            'message' => $message,
            'content' => base64_encode($content),
            'sha'     => $sha,
            'branch'  => 'main'
        ]);
        return $this->request('PUT', $url, $body);
    }

    private function buildUrl($path) {
        return "{$this->apiBase}{$this->user}/{$this->repo}/contents/" . ltrim($path, '/') . "?ref=main";
    }

    private function request($method, $url, $body = null) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'DevBotAI-PHP',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . GITHUB_TOKEN,
                'Accept: application/vnd.github+json'
            ],
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $http, 'response' => json_decode($response, true)];
    }
}
