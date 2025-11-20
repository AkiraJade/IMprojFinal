<?php
class ProfanityFilter {
    // List of profane words to filter
    private static $badWords = [
        // English profanity
        'fuck', 'shit', 'asshole', 'bitch', 'cunt', 'dick', 'pussy', 'cock', 'whore', 'slut',
        'bastard', 'dickhead', 'piss', 'crap', 'damn', 'fag', 'faggot', 'retard', 'nigger',
        'nigga', 'chink', 'spic', 'kike', 'coon', 'twat', 'wanker', 'bollocks', 'arsehole',
        'bloody', 'bugger', 'cow', 'crikey', 'cunt', 'minge', 'prick', 'pissed', 'pissed off',
        'piss off', 'shitty', 'shite', 'twat', 'wank', 'wanker', 'whore',
        
        // Filipino/Tagalog profanity
        'puta', 'putang ina', 'putangina', 'puta ina', 'gago', 'gaga', 'bobo', 'tanga', 'ulol',
        'bobo', 'bubu', 'bubuwit', 'burat', 'puking ina', 'pukina', 'pota', 'potang ina',
        'potangina', 'tanga', 'tarantado', 'ulol', 'unggoy', 'yawa', 'yawwa', 'leche', 'lintik',
        'pakshet', 'pakyu', 'peste', 'pukinang ina', 'puta ka', 'putang ina mo', 'shet', 'sira ulo',
        'suso mo', 'tamod', 'tanga', 'tanga ka', 'tarantado', 'timang', 'tite', 'tungaw', 'ugok',
        'ulol', 'ungas', 'ungas', 'ungas ka', 'yawa', 'yawa ka'
    ];

    /**
     * Check if text contains profanity
     * 
     * @param string $text The text to check
     * @return bool True if profanity is found, false otherwise
     */
    public static function hasProfanity($text) {
        if (empty($text)) {
            return false;
        }

        $text = strtolower($text);
        
        foreach (self::$badWords as $word) {
            // Using word boundaries to match whole words only
            if (preg_match("/\b" . preg_quote($word, '/') . "\b/i", $text)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Filter profanity in text by replacing with asterisks
     * 
     * @param string $text The text to filter
     * @param string $replacement Character to replace profanity with (default: *)
     * @return string Filtered text
     */
    public static function filter($text, $replacement = '*') {
        if (empty($text)) {
            return $text;
        }

        foreach (self::$badWords as $word) {
            $pattern = "/\b" . preg_quote($word, '/') . "\b/i";
            $replacementStr = str_repeat($replacement, strlen($word));
            $text = preg_replace($pattern, $replacementStr, $text);
        }
        
        return $text;
    }

    /**
     * Add custom words to the profanity filter
     * 
     * @param array $words Array of words to add
     */
    public static function addBadWords(array $words) {
        self::$badWords = array_merge(self::$badWords, $words);
        self::$badWords = array_unique(self::$badWords);
    }

    /**
     * Get the current list of bad words
     * 
     * @return array Array of bad words
     */
    public static function getBadWords() {
        return self::$badWords;
    }
}
