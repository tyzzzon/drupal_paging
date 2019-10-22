<?php
/**
 * @file
 * Contains Drupal\paging8\Plugin\Filter\FilterPaging8
 */

namespace Drupal\paging8\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides paging functionality.
 *
 * @Filter(
 *   id = "filter_paging8",
 *   title = @Translation("Paging8 Filter"),
 *   description = @Translation("Paging filter for Drupal 8"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterPaging8 extends FilterBase {

  const PAGE_TITLES_MARKER_START = '<!--pagetitles--';
  const PAGE_TITLES_MARKER_END = '--pagetitles-->';

  /**
   * {@inheritdoc}
   */

  public function process($text, $langcode) {

    // Check if there are multiple pages at all; if not, there's no need to process things.
    if (strpos($text, '!--pagebreak--') != FALSE) {

      // Collects and validate the ?page= argument.
      $page_number = \Drupal::request()->query->get('page');
      $page_number = is_numeric($page_number) ? $page_number : 0;

      // Split text and count pages, then render the requested page.
      $splitted_array = explode('<!--pagebreak-->', $text);
      $page_count = count($splitted_array);
      $splitted = $splitted_array[$page_number];

      $current_path = \Drupal::service('path.current')->getPath();
      $path_alias = \Drupal::service('path.alias_manager')
        ->getAliasByPath($current_path, $langcode);

      if ($this->settings['paging8_show_page_titles'] == TRUE) {
        // Parses or generate Page titles.
        if (strpos($text, '!--pagetitles--') != FALSE) {
          // Extracts title section if exists.
          $page_titles_start_pos = strpos($text, static::PAGE_TITLES_MARKER_START) + strlen(static::PAGE_TITLES_MARKER_START);
          $page_titles_end_pos = strpos($text, static::PAGE_TITLES_MARKER_END);
          $page_titles_length = $page_titles_end_pos - $page_titles_start_pos;
          $page_titles = substr($text, $page_titles_start_pos, $page_titles_length);
          $page_titles_array = explode('||', $page_titles);
        }
        else {
          for ($i = 0; $i < $page_count; $i++) {
            $page_titles_array[] = $this->t('Page') . ' ' . strval($i);
          }
        }

        $renderable = [
            '#theme' => 'title_list',
            '#title_list_array' => [
                'page_titles_array' => $page_titles_array,
                'path_alias' => $path_alias,
                'page_array_index' => $page_number,
            ],
        ];
        $page_title_list = \Drupal::service('renderer')->render($renderable);
      }
      else {
          $page_title_list = '';
      }

      // This is non-conditional.
      if (strpos($splitted, '!--pagetitles--') != FALSE) {
        $splitted = substr($splitted, 0, strpos($splitted, static::PAGE_TITLES_MARKER_START)) . substr($splitted, strpos($splitted, static::PAGE_TITLES_MARKER_END) + strlen(static::PAGE_TITLES_MARKER_END), strlen($splitted));
      }

      if (($this->settings['paging8_show_pager_top'] == TRUE) or ($this->settings['paging8_show_pager_bottom'] == TRUE)) {
        $page_number == 1 ? $prev_page_arg = '' : $prev_page_arg = '?page=' . strval($page_number - 1);
        $next_page_arg = '?page=' . strval($page_number + 1);
        $page_titles_start_pos = strpos($text, static::PAGE_TITLES_MARKER_START) + strlen(static::PAGE_TITLES_MARKER_START);
        $page_titles_end_pos = strpos($text, static::PAGE_TITLES_MARKER_END);
        $page_titles_array = [];
        if ($page_titles_start_pos && $page_titles_end_pos) {
          $page_titles_length = $page_titles_end_pos - $page_titles_start_pos;
          $page_titles = substr($text, $page_titles_start_pos, $page_titles_length);
          $page_titles_array = explode('||', $page_titles);
        }
        if ($this->settings['paging8_show_pager_top'] == TRUE) {
            $renderable = [
                '#theme' => 'top_pager',
                '#top_pager_array' => [
                    'current_page_number' => '#' . ($page_number + 1),
                    'current_page_title' => $page_titles_array[$page_number],
                    'path_alias' => $path_alias,
                    'prev_page_arg' => $prev_page_arg,
                    'next_page_arg' => $next_page_arg,
                    'page_array_index' => $page_number,
                    'page_count' => $page_count,

                ],
            ];
            $pager_top = \Drupal::service('renderer')->render($renderable);
        }
        else {
          $pager_top = '';
        }

        if ($this->settings['paging8_show_pager_bottom'] == TRUE) {
            $renderable = [
                '#theme' => 'bottom_pager',
                '#bot_pager_array' => [
                    'path_alias' => $path_alias,
                    'prev_page_arg' => $prev_page_arg,
                    'next_page_arg' => $next_page_arg,
                    'page_array_index' => $page_number,
                    'page_count' => $page_count,

                ],
            ];
            $pager_bottom = \Drupal::service('renderer')->render($renderable);
        }
        else {
          $pager_bottom = '';
        }
      }

      // Renders the final result and loads style.css.
      $result = new FilterProcessResult($pager_top . $splitted . $pager_bottom . $page_title_list);

      if ($this->settings['paging8_load_css'] == TRUE) {
        $result->setAttachments(['library' => ['paging8/paging8.theme'],]);
      }
    }
    else {
      $result = new FilterProcessResult($text);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */

  // Options for pager placement.
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['paging8_show_pager_top'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show simple pager on top'),
      '#default_value' => $this->settings['paging8_show_pager_top'],
      '#description' => $this->t('Display a simple prev/next pager above the content.'),
    ];
    $form['paging8_show_pager_bottom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show simple pager on bottom'),
      '#default_value' => $this->settings['paging8_show_pager_bottom'],
      '#description' => $this->t('Display a simple prev/next pager below the content'),
    ];
    $form['paging8_show_page_titles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show page list'),
      '#default_value' => $this->settings['paging8_show_page_titles'],
      '#description' => $this->t('Display clickable page list after the content.</br>Format: &lt;!--pagetitles--Title1||Title2--pagetitles--&gt;.</br>Position of the list is not relevant.'),
    ];
    $form['paging8_load_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load paging8.basic.css'),
      '#default_value' => $this->settings['paging8_load_css'],
      '#description' => $this->t('Check to load the default css. If you want to customize, uncheck this option and style the pager with your theme css.'),
    ];
    return $form;
  }

}