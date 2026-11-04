<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class Html
{
    /**
     * Keep only the formatting the rich text editor can produce. This is the
     * only version of stored HTML that may be rendered in a page.
     */
    public static function clean(string $html): string
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('a', ['href', 'title'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(120000);

        foreach (['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote'] as $element) {
            $config = $config->allowElement($element);
        }

        return (new HtmlSanitizer($config))->sanitize($html);
    }

    /**
     * Plain text for places like tables, with block boundaries kept as spaces.
     */
    public static function text(string $html): string
    {
        $spaced = preg_replace('#</(p|li|h2|h3|blockquote)>|<br\s*/?>#i', '$0 ', static::clean($html));

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5)));
    }

    /**
     * An editor left empty still sends markup like <p></p>, so required rules need this.
     */
    public static function emptyIfBlank(?string $html): string
    {
        return static::text((string) $html) === '' ? '' : (string) $html;
    }
}
