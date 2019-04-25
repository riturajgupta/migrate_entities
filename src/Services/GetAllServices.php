<?php

/**
 * @file
 * Contains Drupal\migrate_entities\GetContentTypes.
 *
 * This class is tied into Drupal's config, but it doesn't have to be.
 *
 */

namespace Drupal\migrate_entities\Services;
use Drupal\Core\Config\ConfigFactory;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Datetime;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\file\Entity\File;

/**
 * Class GetAllServices.
 *
 * @package Drupal\nettv
 */
class GetAllServices {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;
  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->config_factory = $config_factory;
  }
  
  /**
   * In this method we are using the Drupal config service to
   * load the variables. Similar to Drupal 7 variable_get().
   * It also uses the new l() function and the Url object from core.
   * At the end of the day, we are just returning a string.
   * This could be refactored to use a Twig template in a future tutorial.
   */
   
  
  public function snp_select_create_csv($entity_type, $content_type) {
    $csv = array();
    $type = 'csv';
    if($entity_type == 'taxonomy'){
      $csv = ['Vocabolary','Term1','Term2','Term3','Term4'];
      $filename = $entity_type . '_template.csv';
    }
    else{
      $labelarray = $this->snp_get_field_list($entity_type,$content_type, $type);
      foreach ($labelarray as $key => $value) {
        $csv[] =  $value;
      }
      $filename = $content_type . '_template.csv';
    }

  
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={$filename}");
    header("Expires: 0");
    header("Pragma: public");
    $fh = @fopen('php://output', 'w');
  
    // Put the data into the stream.
    fputcsv($fh, $csv);
    fclose($fh);
    // Make sure nothing else is sent, our file is done.
    exit;
  }
   

  public function getAllColumnHeaders($fileuri) {
    $handle = fopen($fileuri, 'r');
    $row = fgetcsv($handle);
    foreach ($row as $value) {
      // code...
      $key = strtolower(preg_replace('/\s+/', '_', $value));
      $column[$key] = $value;
    }
    return $column;
  }
   
  public function outputCsv($fileName, $assocDataArray)
  {   
    $csv_handler = fopen ($fileName,'w');
    fputcsv($csv_handler, array_keys($assocDataArray['0']));
    foreach ($assocDataArray as $values){
      fputcsv($csv_handler, $values);
    }
  
    $directory = 'public://upload_csv/';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $file = file_get_contents($host.'/'.$fileName);
      
    $file_save = file_save_data($file, $directory.$fileName,FILE_EXISTS_RENAME);
    fclose ($csv_handler);
    
    return $file_save;
  }

  public function csvToArray($filename='', $delimiter) {
    if(!file_exists($filename) || !is_readable($filename)) return FALSE;
    $header = NULL;
    $data = array();

    if (($handle = fopen($filename, 'r')) !== FALSE ) {
      while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
      {
        if(!$header){
          $header = $row;
        }else{
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }

    return $data;
  }

  public function csvHeader($path='') {
    
    $file = fopen($path, 'r');
    $headers = fgetcsv($file);
     
    return $headers;
  }

  public function fieldTermType($field_name, $bundleType) {
    $bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('node', $bundleType);
    $field_definition = $bundle_fields[$field_name];     
    $field_type = !empty($field_definition->getType()) ? $field_definition->getType() : '';
    $term_type = !empty($field_definition->getSettings()['target_type']) ? $field_definition->getSettings()['target_type'] : '';
    
    return [
      'entity_type' => $term_type,
      'field_type'  => $field_type,
      'field_definition' => $field_definition,
    ];
  }

  public function taxonomyTermProcess($field_name, $bundleType) {
    $type_result = $this->FieldTermType($field_name, $bundleType);    
    
    if($type_result['field_type'] == 'entity_reference' && $type_result['entity_type'] == 'taxonomy_term') {
      $field_definition = $type_result['field_definition'];
      $term_machine_name = $field_definition->getSetting('handler_settings')['target_bundles'];
    }
    if(!empty($term_machine_name)) {
      foreach($term_machine_name as $term_key => $term_value) {
        $machine_name = $term_value;
      }
    }
     
    if(!empty($machine_name)) { 
      $process = [
        '0' => [
          'plugin' => 'explode',
          'source' => $field_name,
          'delimiter' => ';',
          'limit' => 2,
        ],
        '1' => [
          'plugin' => 'entity_generate',
          'entity_type' => 'taxonomy_term',
          'bundle_key' => 'vid',
          'bundle' => $machine_name,
        ],
      ];
    }

    if(!empty($machine_name)) {
      return $process;
    } 
    else {
      return;
    }
  }

  public function fileProcess($field_name, $bundleType) {
    $type_result = $this->FieldTermType($field_name, $bundleType);
    
    if($type_result['field_type'] == 'file' && $type_result['entity_type'] == 'file') {
      $field_definition = $type_result['field_definition'];
      $machine_name = $field_definition->get('field_name');
    }
    
    if(!empty($machine_name)) { 
      $process = [
        'plugin' => 'file_import',
        'source' => 'field_file',
        'destination' => 'constants/file_destination',
      ];

      return $process;
    } 
    else {
      return;
    }

  }

  public function imageProcess($field_name, $bundleType) {
    $type_result = $this->FieldTermType($field_name, $bundleType);
    if($type_result['field_type'] == 'image' && $type_result['entity_type'] == 'file') {
      $field_definition = $type_result['field_definition'];    
      $machine_name = $field_definition->get('field_name');
    }
      
    if(!empty($machine_name)) { 
      $process = [
          'plugin' => 'image_import',
          'source' => 'field_image',
          'destination' => 'constants/image_destination',
        ];
      return $process;
    } 
    else {
      return;
    }
  }

}
