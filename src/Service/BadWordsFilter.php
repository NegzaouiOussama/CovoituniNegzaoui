<?php

namespace App\Service;

class BadWordsFilter
{
    /**
     * Liste des mots à filtrer
     *
     * @var array
     */
    private $badWords = [
        'fuck', 
        'fuck you',
        'shit',
        'bitch',
        'ass',
        'asshole',
        'bastard',
        'cunt',
        'dick',
        'whore',
        'slut',
        'piss',
        'pussy',
        'nazi',
        'motherfucker',
        'nigger',
        'faggot',
        'damn',
        'hell',
        'nigga',
        'retard',
        'retarded'
    ];

    /**
     * Vérifie si le texte contient des mots inappropriés
     *
     * @param string|null $text
     * @return bool
     */
    public function containsBadWords(?string $text): bool
    {
        if ($text === null) {
            return false;
        }

        $text = strtolower($text);
        
        foreach ($this->badWords as $word) {
            if (strpos($text, $word) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Récupère la liste des mots inappropriés trouvés dans le texte
     *
     * @param string|null $text
     * @return array
     */
    public function getFoundBadWords(?string $text): array
    {
        if ($text === null) {
            return [];
        }

        $text = strtolower($text);
        $foundWords = [];
        
        foreach ($this->badWords as $word) {
            if (strpos($text, $word) !== false) {
                $foundWords[] = $word;
            }
        }
        
        return $foundWords;
    }
} 