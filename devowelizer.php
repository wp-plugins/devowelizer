<?php
/*
Plugin Name: RR Devowelizer
Version: 1.21
Plugin URI: http://www.richardsramblings.com/plugins/wp-devowelizer/
Description: The Devowelizer plugin replaces the vowels in most bad language within your own content and from comments left by visitors. If the word "devowelizer" was a bad word, it would appear as "dëvøwëlìzër". Visual profanity filtering, without censoring.
Author: Richard D. LeCour
Author URI: http://www.richardsramblings.com/

Copyright (c) 2006-2012 Richard D. LeCour

TO INSTALL INTO WORDPRESS:
1. Copy all the files into a new 'wp-content/plugins/devowelizer/' folder.
2. Enable the plugin in the WordPress Plugin Admin panel.

Visit http://www.richardsramblings.com/plugins/wp-devowelizer/ for more information and options.
*/

global $devowelizer_old_letters, $devowelizer_new_letters, $devowelizer_list;
$devowelizer_old_letters = array(
      "a", "e", "i", "o", "u", "y",
      "A", "E", "I", "O", "U", "Y",
      "c", "C", "D", "n", "s", "t"
);
$devowelizer_new_letters = array(
      "&#225;", "&#235;", "&#236;", "&#248;", "&#251;", "&#255;", // áëìøûÿ
      "&#195;", "&#202;", "&#205;", "&#216;", "&#217;", "&#376;", // ÃÊÍØÙY
      "&#231;", "&#199;", "&#208;", "&#326;", "&#353;", "&#359;"  // çÇÐņšŧ
);

function devowelizer($text) {
   return devowelizer_work($text);
}

function devowelizer_cache($text) {
   $result = false;
   $key = "devowelizer".trim($text);
   if (defined('POC_CACHE')) {
      $cache = new POC_Cache();
      $result = $cache->fetch($key);
   }
   if (false === $result) {
      $result = devowelizer($text);
      if (defined('POC_CACHE')) $cache->store($key, $result);
   }
   return $result;
}

function devowelizer_work($text) {
   global $devowelizer_list;
   if(empty($devowelizer_list)) {
      $word_list = array();
      $file_name = dirname(__FILE__)."/words.txt"; // GRABS THE DEFAULT WORDS
         if (file_exists($file_name)) {
            $word_list += file($file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
         }
      $file_name = dirname(__FILE__)."/words-custom.txt"; // STORES YOUR CUSTOM WORDS
         if (file_exists($file_name)) {
            $word_list += file($file_name);
         }
      $devowelizer_list[] = "/()\[devowelize[r]?\]([^\[]+)\[\/devowelize[r]?\]/si";
      foreach ($word_list as $word) {
         $word = trim($word);
         if (substr($word, 0, 1) == '#') { continue; }
         $regex = "/(href=[\'\"]?[\w\-\.\/\:]*)?(";
         if (substr($word, strlen($word) -1, 1) == '*') {
            if (substr($word, 0, 1) == '*') {
               $regex .= "[\w\-]*".substr($word, 1, strlen($word) -2)."[\w\-]*";
            } else {
               $regex .= "\b".substr($word, 0, strlen($word) -1)."[\w\-]*";
            }
         } else {
            $regex .= "\b".$word."\b";
         }
         $regex .= ")/si";
         $devowelizer_list[] = $regex;
      }
   }
   $text = preg_replace_callback($devowelizer_list, "devowelizer_callback", $text);
   return $text;
}

function devowelizer_callback($matches){
   global $devowelizer_old_letters, $devowelizer_new_letters;
   $matched_word = $matches[2];
   if (empty($matches[1])) {
      $matched_word = str_replace($devowelizer_old_letters, $devowelizer_new_letters, $matched_word);
   }
   return $matches[1].$matched_word;
}

add_option("devowelize_admin_pages", "yes");

if (!is_admin() || get_option('devowelize_admin_pages') == 'yes' ) {
   add_filter('the_content', 'devowelizer', 11);
   add_filter('the_excerpt', 'devowelizer', 11);
   add_filter('get_comment_text', 'devowelizer', 11);
   add_filter('get_comment_excerpt', 'devowelizer', 11);
   add_filter('get_comment_author', 'devowelizer', 11);
}

?>