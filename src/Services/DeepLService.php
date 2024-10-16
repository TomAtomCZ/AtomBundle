<?php

namespace TomAtom\AtomBundle\Services;

use DeepL\DeepLException;
use DeepL\Translator;
use Exception;

class DeepLService
{
    private string $deeplKey;

    public function __construct(string $deeplKey)
    {
        $this->deeplKey = $deeplKey;
    }

    /**
     * Translates a given text from the source language to the target language.
     * @param string $text The text to be translated.
     * @param string $sourceLang The language code of the source text.
     * @param string $targetLang The language code of the target translation.
     * @return string The translated text.
     * @throws DeepLException
     * @throws Exception
     */
    public function translate(string $text, string $sourceLang, string $targetLang): string
    {
        $translator = new Translator($this->deeplKey);
        if ($targetLang === 'en') {
            $targetLang = 'en-GB';
        }
        return $translator->translateText($text, $sourceLang, $targetLang);
    }
}
