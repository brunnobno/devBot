<?php
// Editor.php

class Editor {
    /**
     * Substitui uma string exata por outra no conteúdo do arquivo.
     */
    public static function substituir($conteudo, $buscar, $substituir) {
        return str_replace($buscar, $substituir, $conteudo);
    }

    /**
     * Insere uma função PHP no final do arquivo, se ela não existir ainda.
     */
    public static function adicionarFuncao($conteudo, $assinatura, $codigo) {
        if (strpos($conteudo, $assinatura) !== false) {
            return $conteudo; // já existe
        }
        return trim($conteudo) . "\n\n" . $codigo . "\n";
    }

    /**
     * Remove uma função inteira pelo nome.
     */
    public static function removerFuncao($conteudo, $nomeFuncao) {
        $pattern = "/function\\s+$nomeFuncao\\s*\\([^)]*\\)\\s*\\{(?:[^{}]*|(?R))*\\}/m";
        return preg_replace($pattern, '', $conteudo);
    }
}
