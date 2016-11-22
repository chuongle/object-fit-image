<?php

namespace Drupal\object_fit_image\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'object_fit_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "object_fit_image_formatter",
 *   label = @Translation("Object Fit Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ObjectFitImageFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

/**
 * The current user.
 *
 * @var \Drupal\Core\Session\AccountInterface
 */
 protected $currentUser;

/**
 * The link generator.
 *
 * @var \Drupal\Core\Utility\LinkGeneratorInterface
 */
 protected $linkGenerator;

/**
 * The image style entity storage.
 *
 * @var \Drupal\Core\Entity\EntityStorageInterface
 */
 protected $imageStyleStorage;

/**
 * Constructs an ImageFormatter object.
 *
 * @param string $plugin_id
 *   The plugin_id for the formatter.
 * @param mixed $plugin_definition
 *   The plugin implementation definition.
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
 *   The definition of the field to which the formatter is associated.
 * @param array $settings
 *   The formatter settings.
 * @param string $label
 *   The formatter label display setting.
 * @param string $view_mode
 *   The view mode.
 * @param array $third_party_settings
 *   Any third party settings settings.
 * @param \Drupal\Core\Session\AccountInterface $current_user
 *   The current user.
 * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
 *   The link generator service.
 * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
 *   The entity storage for the image.
 */
 public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, LinkGeneratorInterface $link_generator, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->linkGenerator = $link_generator;
    $this->imageStyleStorage = $image_style_storage;
  }

/**
 * {@inheritdoc}
 */
 public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
   return new static(
     $plugin_id,
     $plugin_definition,
     $configuration['field_definition'],
     $configuration['settings'],
     $configuration['label'],
     $configuration['view_mode'],
     $configuration['third_party_settings'],
     $container->get('current_user'),
     $container->get('link_generator'),
     $container->get('entity_type.manager')->getStorage('image_style')
   );
 }

/**
 * {@inheritdoc}
 */
 public static function defaultSettings() {
   return [
     'image_style' => '',
     'image_link' => '',
     'selector' => '',
   ] + parent::defaultSettings();
 }

/**
 * {@inheritdoc}
 */
 public function settingsForm(array $form, FormStateInterface $form_state) {
  $settings = $this->getSettings();
   $image_styles = image_style_options(FALSE);
   $element['image_style'] = [
     '#title' => t('Image style'),
     '#type' => 'select',
     '#default_value' => $this->getSetting('image_style'),
     '#empty_option' => t('None (original image)'),
     '#options' => $image_styles,
     '#description' => [
       '#markup' => $this->linkGenerator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
       '#access' => $this->currentUser->hasPermission('administer image styles'),
     ],
   ];
   // The selector for the background property.
   $element['selector'] = array(
     '#type' => 'textfield',
     '#title' => t('Selector'),
     '#description' => t('A valid CSS selector for parent wrapper.'),
     '#default_value' => $settings['selector'],
   );
   return $element;
 }

/**
 * {@inheritdoc}
 */
 public function settingsSummary() {
   $summary = [];
   $image_styles = image_style_options(FALSE);

   // Unset possible 'No defined styles' option.
   unset($image_styles['']);

   // Styles could be lost because of enabled/disabled modules that defines
   // their styles in code.
   $image_style_setting = $this->getSetting('image_style');
   if (isset($image_styles[$image_style_setting])) {
     $summary[] = t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
   }
   else {
     $summary[] = t('Original image');
   }

   $selector = $this->getSetting('selector');
   if(isset($selector)) {
     $summary[] = t('Selector: ' . $selector );
   }
   return $summary;
 }

/**
 * {@inheritdoc}
 */
 public function viewElements(FieldItemListInterface $items, $langcode) {
   $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

   // Early opt-out if the field is empty.
   if (empty($files)) {
     return $elements;
   }

   $url = NULL;
   $image_link_setting = $this->getSetting('image_link');
   // Check if the formatter involves a link.
   if ($image_link_setting == 'content') {
     $entity = $items->getEntity();
     if (!$entity->isNew()) {
       $url = $entity->urlInfo();
     }
   }
   elseif ($image_link_setting == 'file') {
     $link_file = TRUE;
   }

   $image_style_setting = $this->getSetting('image_style');
   // Collect cache tags to be added for each item in the field.
   $cache_tags = [];
   if (!empty($image_style_setting)) {
     $image_style = $this->imageStyleStorage->load($image_style_setting);
     $cache_tags = $image_style->getCacheTags();
   }

   $selector = $this->getSetting('selector');

   foreach ($files as $delta => $file) {
     if (isset($link_file)) {
       $image_uri = $file->getFileUri();
       $url = Url::fromUri(file_create_url($image_uri));
     }

     $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

     // Extract field item attributes for the theme function, and unset them
     // from the $item so that the field template does not re-render them.
     $item = $file->_referringItem;
     $item_attributes = $item->_attributes;
     unset($item->_attributes);
     $elements[$delta] = [
       '#theme' => 'object_fit_image_formatter',
       '#item' => $item,
       '#item_attributes' => $item_attributes,
       '#image_style' => $image_style_setting,
       '#url' => $url,
       '#cache' => [
         'tags' => $cache_tags,
       ],
       '#attached' => [
          'library' =>  [
            'object_fit_image/object_fit_image',
          ],
        ],
     ];
   }

   return $elements;
 }
}