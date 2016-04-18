<?php namespace AgreableInstantArticlesPlugin\Controllers;

use TimberPost;

use Facebook\InstantArticles\Client\Client;
use Facebook\InstantArticles\Elements\InstantArticle;
use Facebook\InstantArticles\Transformer\Transformer;

class SaveController {

  function __construct(TimberPost $post) {
    $this->post = $post;
    $take_live = !empty($post->instant_articles_is_preview);
    $permalink = get_permalink($this->post->id);
    $url = "$permalink/instant-articles?bypass";
    $instant_article = $this->build_article_object($url);
    $client = $this->setup_client();
    try {
      $client->importArticle($instant_article, $take_live);
    } catch (Exception $e) {
      echo 'Could not import the article: '.$e->getMessage();
    }
  }

  public function setup_client() {
    $client = Client::create(
      get_option('instant_articles_app_id'),
      get_option('instant_articles_app_secret'),
      get_option('instant_articles_page_token'),
      get_option('instant_articles_page_id'),
      true// development environment?
    );
    return $client;
  }

  public function build_article_object($url) {
    try {
      $html = file_get_contents($url, true);
    } catch (\Exception $e) {
      echo $e->getMessage();
    }
    $rules_file_content = file_get_contents("simple-rules.json", true);
    $transformer = new Transformer();

    //$transformer->loadRules($rules_file_content);
    $instant_article = InstantArticle::create();
    libxml_use_internal_errors(true);
    $document = new \DOMDocument();
    $document->loadHTML($html);
    libxml_use_internal_errors(false);
    ob_start();
    $transformer->transform($instant_article, $document);
    ob_end_clean();
    $warnings = $transformer->getWarnings();
    print_r($warnings);die;
    return $instant_article;

  }

}
