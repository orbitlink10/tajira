<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h2', 'h3', 'h4',
        'ul', 'ol', 'li', 'a', 'blockquote', 'table', 'thead', 'tbody',
        'tr', 'th', 'td', 'img', 'figure', 'figcaption', 'hr', 'code', 'pre',
        'div', 'span', 'dl', 'dt', 'dd',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel', 'class', 'id'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'decoding', 'class'],
        'th' => ['scope', 'colspan', 'rowspan', 'class'],
        'td' => ['colspan', 'rowspan', 'class'],
        '*' => ['class', 'id', 'dir', 'lang'],
    ];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        if (! class_exists(DOMDocument::class)) {
            return strip_tags($html, '<p><br><strong><em><ul><ol><li><h2><h3><h4><a>');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="tajira-content">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return '';
        }

        $wrapper = $document->getElementById('tajira-content');

        if (! $wrapper) {
            return '';
        }

        $this->sanitizeNode($wrapper);

        $result = '';

        foreach ($wrapper->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private function sanitizeNode(DOMNode $node): void
    {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            /** @var DOMElement $node */
            $tag = strtolower($node->nodeName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $node->parentNode?->removeChild($node);

                return;
            }

            $allowed = array_merge(self::ALLOWED_ATTRIBUTES['*'], self::ALLOWED_ATTRIBUTES[$tag] ?? []);
            $attributes = [];

            foreach (iterator_to_array($node->attributes) as $attribute) {
                $attributes[] = strtolower($attribute->nodeName);
            }

            foreach ($attributes as $attributeName) {
                $value = $node->getAttribute($attributeName);

                if (! in_array($attributeName, $allowed, true)) {
                    $node->removeAttribute($attributeName);

                    continue;
                }

                if (in_array($attributeName, ['href', 'src'], true)) {
                    $value = trim($value);

                    if (preg_match('/^\s*(javascript|data|vbscript):/i', $value)) {
                        $node->removeAttribute($attributeName);

                        continue;
                    }

                    if ($attributeName === 'src' && ! preg_match('#^(https?://|/)#i', $value)) {
                        $node->removeAttribute($attributeName);

                        continue;
                    }
                }

                if ($node->getAttribute('target') === '_blank' && $tag === 'a') {
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
            }

            if ($tag === 'img') {
                if (! $node->hasAttribute('alt')) {
                    $node->setAttribute('alt', '');
                }

                $node->setAttribute('loading', 'lazy');
                $node->setAttribute('decoding', 'async');
            }
        }

        if (! $node->hasChildNodes()) {
            return;
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->sanitizeNode($child);
        }
    }
}
