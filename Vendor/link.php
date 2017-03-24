<?php

namespace Vendor\Model;

class Link
{
    /**
     * Removed titles from the content
     *
     * @var array
     */
    protected $removed_titles = null;

    /**
     * Base URL
     *
     * @var string
     */
    protected $base_url = null;

    public function setBaseUrl($base_url)
    {
        $this->base_url = $base_url;
    }

    public function getBaseUrl()
    {
        if (!isset($this->base_url)) {
            return $_SERVER['HTTP_HOST'];
        }
        return $this->base_url;
    }

    public function removeTitles($content)
    {
        $number_of_titles = preg_match_all('#<h([1-6])>(.+?)</h\1>#is', $content, $titles);

        if ($number_of_titles >= 1) {

            $title_texts = $titles[2];

            foreach ($title_texts as $title_text) {
                $title_id = uniqid();
                $content = str_replace($title_text, $title_id, $content);
                $title_text_without_links = preg_replace('/<\/?a(.|\s)*?>/', '', $title_text);
                $this->removed_titles[$title_id] = $title_text_without_links;
            }
        }

        return $content;
    }

    public function addRemovedTitles($content)
    {
        foreach ($this->removed_titles as $tile_key => $title_text) {
            $content = str_replace($tile_key, $title_text, $content);
        }

        $this->removed_titles = null;

        return $content;
    }

    public function cleanContentFromLinks($content)
    {
        $non_linked_content = preg_replace('#<a.*?>(.*?)</a>#i', '\1', $content);
        if (preg_match('#<a.*?>(.*?)</a>#i', $non_linked_content)) {
            return $this->cleanContentFromLinks($non_linked_content);
        }

        return $non_linked_content;
    }

    public function quoteSpecificSymbols($string)
    {
        //quote all special regular expression characters
        $string = preg_quote($string);

        return preg_replace('/\%/', '\%', $string);
    }

    public function getInternalLinkHtml($label, $urlSlug)
    {
        $url = $this->getBaseUrl() . $urlSlug;
        $html_link = sprintf('<a href="%s">%s</a>', $url, $label);

        return $html_link;
    }

    public function linkContent($content, $keyword, $url_slug)
    {
        $this->removeTitles($content);

        //create html internal link
        $link = $this->getInternalLinkHtml($keyword, $url_slug);
        //quote specific symbols
        $keyword = $this->quoteSpecificSymbols($keyword);
        $linked_content = preg_replace('%\b' . $keyword . '(?![^<]*</a>)\b%', $link, $content, 1);

        $this->addRemovedTitles($content);

        return $linked_content;
    }
}