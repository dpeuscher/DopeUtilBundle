<?php

namespace Dope\UtilBundle\Services;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * @category  stocks
 * @copyright Copyright (c) 2017 Dominik Peuscher
 */
class HtmlXmlRepairService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const OPTION_REMOVE_SCRIPTS = 1;
    const OPTION_REMOVE_STYLE = 2;
    const OPTION_TRANSLATE_HTML_ENTITIES = 4;
    const OPTION_REPAIR_IMG_AND_BR_TAGS = 8;
    const OPTION_REPAIR_WRONG_AMP = 16;
    const OPTION_REMOVE_WRONG_HTML_TAGS = 32;
    const OPTION_REMOVE_ILLEGAL_CHARS_FROM_TAGS = 64;
    const OPTION_LOWERCASE_TAGS = 128;
    const OPTION_REMOVE_INVALID_UTF8_CHARS = 256;
    const OPTION_FIX_ATTRIBUTES = 512;
    const OPTION_SPECIAL_CASES = 1024;

    const HTMLSPECIALCHARS_WHITELIST = ['&gt;', '&lt;', '&quot;', '&apos;', '&amp;'];

    /**
     * HtmlXmlRepairService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function extractContent(
        string $html,
        string $regex,
        $options = 1 + 2 + 4 + 8 + 16 + 32 + 64 + 128 + 256 + 512 + 1024
    ) {
        if ($options | self::OPTION_TRANSLATE_HTML_ENTITIES) {
            foreach (get_html_translation_table(HTML_ENTITIES) as $char => $code) {
                if (in_array($code, static::HTMLSPECIALCHARS_WHITELIST)) {
                    continue;
                }
                $html = str_replace($code, $char, $html);
            }
        }

        $startTime = microtime(true);
        $html = trim(preg_replace([
            $regex,
            #'#\n#',
            #'#\r#',
            '# +#',
        ], [
            '\1',
            #' ',
            #' ',
            ' ',
        ],
            $html));
        $this->logger->debug('Extracted content with regex ' . $regex . ' (' . number_format(microtime(true) - $startTime,
                2) . ' s)');

        if ($options | self::OPTION_REMOVE_SCRIPTS) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('_<script[^>]*>.*</script>_sU', $html)) {
                $fixes++;
                $html = preg_replace('_<script[^>]*>.*</script>_sU', ' ', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "remove scripts": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }
        if ($options | self::OPTION_REMOVE_STYLE) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('#<style[^>]*>.*</style>#sU', $html)) {
                $fixes++;
                $html = preg_replace('#<style[^>]*>.*</style>#sU', ' ', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "remove style": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }
        if ($options | self::OPTION_REPAIR_IMG_AND_BR_TAGS) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('#<(?:img|(n?br))(?:[^>]*[^/])?\s*>#sU', $html)) {
                $fixes++;
                $html = preg_replace('#(<(?:img|(?:n?br))(?:[^>]*[^>/])?\s*)(>)#sU', '\1/\2', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "repair img and br tags": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }
        if ($options | self::OPTION_REPAIR_WRONG_AMP) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('#(<[^>]*(?:src|href)="[^"&]*(?:&[^;"]+;[^"&]*)*)&((?:(?:[a-zA-Z0-9]{0,10}[^a-zA-Z0-9";][^"]*")|(?:[^";]{0,10}")|(?:[^";]{10,}[^"]*"))[^>]*>)#sU',
                $html)) {
                $fixes++;
                $html = preg_replace('#(<[^>]*(?:src|href)="[^"&]*(?:&[^;"]+;[^"&]*)*)&((?:(?:[a-zA-Z0-9]{0,10}[^a-zA-Z0-9";][^"]*")|(?:[^";]{0,10}")|(?:[^";]{10,}[^"]*"))[^>]*>)#sU',
                    '\1&amp;\2', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "repair wrong amp": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match("#(<[^>]*(?:src|href)='[^'&]*(?:&[^;']+;[^'&]*)*)&((?:(?:[a-zA-Z0-9]{0,10}[^a-zA-Z0-9';][^']*')|(?:[^';]{0,10}')|(?:[^';]{10,}[^']*'))[^>]*>)#sU",
                $html)) {
                $fixes++;
                $html = preg_replace("#(<[^>]*(?:src|href)='[^'&]*(?:&[^;']+;[^'&]*)*)&((?:(?:[a-zA-Z0-9]{0,10}[^a-zA-Z0-9';][^']*')|(?:[^';]{0,10}')|(?:[^';]{10,}[^']*'))[^>]*>)#sU",
                    '\1&amp;\2', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "repair wrong amp (2)": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('#>[^<&]*(&[^<;]{0,10};[^<&]*)*&(?:(?:[a-zA-Z0-9]{0,10}[^a-zA-Z0-9<;][^<]*<)|(?:[^<;]{0,10}<)|(?:[^<;]{10,}[^<]*<))#sU',
                $html)) {
                $fixes++;
                $html = preg_replace('#(>[^<&]*(?:&[^<;]{0,10};[^<&]*)*)&((?:(?:[a-zA-Z0-9]{0,10}[^a-zA-Z0-9<;][^<]*<)|(?:[^<;]{0,10}<)|(?:[^<;]{10,}[^<]*<)))#sU',
                    '\1&amp;\2', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "repair wrong amp (3)": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }

        if ($options | self::OPTION_REMOVE_WRONG_HTML_TAGS) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('_<(?:(?:/?em)|(?:/?html)|e|-|(#[^>]*)|(https?://[^>]*))>_sUi', $html)) {
                $fixes++;
                $html = preg_replace('_<(?:(?:/?em)|(?:/?html)|e|-|(#[^>]*)|(https?://[^>]*))>_sUi', '', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "remove wrong html tags": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }

        if ($options | self::OPTION_REMOVE_ILLEGAL_CHARS_FROM_TAGS) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('/<[[:alnum:]\-_]+(?:(?:\s+[[:alnum:]\-_]+)|(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*([^[:alnum:]\-_>\s\/]+)(?:(?:\s+[[:alnum:]\-_]+)|(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*>/sU',
                $html)) {
                $fixes++;
                $html = preg_replace('/(<[[:alnum:]\-_]+(?:(?:\s+[[:alnum:]\-_]+)|(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*)[^[:alnum:]\-_>\s\/]+((?:(?:\s+[[:alnum:]\-_]+)|(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*>)/sU',
                    '\1 \2', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "remove illegal chars from tags": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }
        if ($options | self::OPTION_FIX_ATTRIBUTES) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('/<[[:alnum:]\-_]+(?:(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*(?:\s+[[:alnum:]\-_]+)(?:(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*>/sU',
                $html)) {
                $fixes++;
                $html = preg_replace('/(<[[:alnum:]\-_]+(?:(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*)(?:\s+[[:alnum:]\-_]+)((?:(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*>)/sU',
                    '\1\2', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "fix attributes": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }
        if ($options | self::OPTION_LOWERCASE_TAGS) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('/<\/?[a-z\-_]*[A-Z]+[a-zA-Z\-_]*(?:(?:\s+[[:alnum:]\-_]+)|(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*>/sU',
                $html)) {
                $fixes++;
                $html = preg_replace_callback('/(<\/?[a-z\-_]*)([A-Z]+[a-zA-Z\-_]*)((?:(?:\s+[[:alnum:]\-_]+)|(?:\s+[[:alnum:]\-_]+="[^"]*")|(?:[[:alnum:]\-_]+=\'[^\']*\'))*>)/sU',
                    function ($word) {
                        return $word[1] . strtolower($word[2]) . $word[3];
                    }, $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "lowercase tags": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }

        if ($options | self::OPTION_REMOVE_ILLEGAL_CHARS_FROM_TAGS) {
            $fixes = 0;
            $startTime = microtime(true);
            while (preg_match('#\x96\x20\x47\x72#sU', $html)) {
                $fixes++;
                $html = preg_replace('#\x96\x20\x47\x72#sU', ' ', $html);
            }
            if ($fixes) {
                $this->logger->debug('Fixed "illegal chars from tags": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }
        if ($options | self::OPTION_SPECIAL_CASES) {
            $fixes = 0;
            $startTime = microtime(true);
            $length = strlen($html);
            $html = preg_replace('#(<p>Sony hat auf der US-Fotomesse Photo Marketing Association \(PMA\) 2010 zahlreiche Konzeptkameras vorgestellt - die .{0,2}berraschung schlechthin ist eine (?:<a href="https://www.golem.de/specials/hybridkamera/" target="_blank">)?Kompaktkamera mit Wechselobjektiven(?:</a>)? .{0,2}hnlich wie die <a href="https://www.golem.de/1002/72853.html" target="_blank">Olympus E-Pen</a>.)#s',
                '\1</p>', $html);
            if (strlen($html) - $length) {
                $fixes++;
            }
            if ($fixes) {
                $this->logger->debug('Fixed "special cases": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                        2) . ' s)');
            }
        }

        $fixes = 0;
        $startTime = microtime(true);
        do {
            if (isset($utf8Error)) {
                unset($utf8Error);
            }
            $repairXml = '<?xml version="1.0" encoding="UTF-8" ?><html>' . $html . '</html>';
            try {
                $xml = new \SimpleXMLElement($repairXml);
            } catch (Exception $exception) {
                if (($options | self::OPTION_REMOVE_ILLEGAL_CHARS_FROM_TAGS) && (!is_null($exception->getPrevious()) && preg_match('#Warning: SimpleXMLElement::__construct\(\): Entity: line \d+: parser error\s*:\s*Input is not proper UTF-8, indicate encoding !\s*Bytes: ([0-9a-fA-Fx ]+)\s*$#s',
                            $exception->getPrevious()->getMessage(), $matches) && $fixes < 1000)
                ) {
                    $utf8Error = $matches[1];
                    $utf8Error = preg_replace('# ?0(x[\dA-Fa-f]{2})#', '\1', $utf8Error);
                    $utf8Error = str_replace('x', '\\x', $utf8Error);
                    $fixes++;
                    $html = preg_replace('#' . $utf8Error . '#', ' ', $html);
                    continue;
                }
                file_put_contents('var/logs/error.' . date('YmdHis') . '.xml', $repairXml);
                throw $exception;
            }
        } while (isset($utf8Error));

        if ($fixes) {
            $this->logger->debug('Fixed "illegal chars from tags (2)": ' . $fixes . ' times (' . number_format(microtime(true) - $startTime,
                    2) . ' s)');
        }

        return $xml;
    }
}
