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

      if ($this->settings['paging8_showpagetitles'] == TRUE) {

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

        $page_title_list = '<div class="paging8-title-list">';
        for ($i = 0; $i < $page_count; $i++) {
          if ($i == 0) {
            $link_string = '<a href="' . $path_alias . '">' . strval($i + 1) . ' - ' . $page_titles_array[$i] . '</a>';
          }
          else {
            $link_string = '<a href="' . $path_alias . '?page=' . strval($i) . '">' . strval($i + 1) . ' - ' . $page_titles_array[$i] . '</a>';
          }
          if ($i == $page_number) {
            $item_render = '<div class="paging8-title-item paging8-title-item-current"><span>»</span>' . strval($i + 1) . '<span> - </span>' . $page_titles_array[$i] . '</div>';
          }
          else {
            $item_render = '<div class="paging8-title-item">' . $link_string . '</div>';
          }
          $page_title_list = $page_title_list . $item_render;
        }
        $page_title_list = $page_title_list . '</div>';
      }

      // This is non-conditional.
      if (strpos($splitted, '!--pagetitles--') != FALSE) {
        $splitted = substr($splitted, 0, strpos($splitted, static::PAGE_TITLES_MARKER_START)) . substr($splitted, strpos($splitted, static::PAGE_TITLES_MARKER_END) + strlen(static::PAGE_TITLES_MARKER_END), strlen($splitted));
      }

      if (($this->settings['paging8_showpagertop'] == TRUE) or ($this->settings['paging8_showpagerbottom'] == TRUE)) {
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
        if (empty($page_titles_array)) {
          $next_page_title = '';
          $previous_page_title = '';
          $current_page_title = '';
        }
        else {
          $next_page_title = '
            <p class="next-page-title col-xs-12">
                ' . $page_titles_array[$page_number + 1] . '
            </p>';
          $previous_page_title = '<p class="previous-page-title col-xs-12">
                ' . $page_titles_array[$page_number - 1] . '
            </p>';
          $current_page_title = '<p class="current-page-title col-xs-8">
                <span class="blue-letters">#' . ($page_number + 1) . '</span>' . $page_titles_array[$page_number] . '
            </p>';
        }
        if ($this->settings['paging8_showpagertop'] == TRUE) {
          if ($page_number == 0) {
            $pager_top = '';
          }
          elseif ($page_number == $page_count - 1) {
            $pager_top = '<div class="paging8 paging8-top"><div class="paging8-prev paging8-prev-full col-xs-2"><a href="' . $path_alias . $prev_page_arg . '">' . $this->t('< <div class="mobile-slider"> Previous</div>') . '
            </a></div>' . $current_page_title . '<div class="col-xs-2"></div></div>';
          }
          else {
            $pager_top = '<div class="paging8 paging8-top"><div class="paging8-prev col-xs-2"><a href="' . $path_alias . $prev_page_arg . '">' . $this->t('< <div class="mobile-slider"> Previous</div>') . '
            </a></div>' . $current_page_title . '<div class="paging8-next col-xs-2"><a href="' . $path_alias . $next_page_arg . '">' . $this->t('<div class="mobile-slider">Next </div> >') . '
            </a></div></div>';
          };
            $renderable = [
                '#theme' => 'top_pager',
                '#top_pager_array' => [
                    'current_page_title' => $current_page_title,
                    'path_alias' => $path_alias,
                    'next_page_arg' => $next_page_arg,
                ],
            ];
            $pager_top = \Drupal::service('renderer')->render($renderable);
        }
        else {
          $pager_top = '';
        }

        if ($this->settings['paging8_showpagerbottom'] == TRUE) {
          if ($page_number == 0) {
            $pager_bottom = '<div class="paging8 paging8-bottom"><span class="page-counter col-xs-12">Page ' . ($page_number + 1) . ' of ' . $page_count . '</span><div class="paging8-next paging8-next-full col-xs-12"><a href="' . $path_alias . $next_page_arg . '" class="col-xs-12">' . $this->t('<div class="mobile-slider">Next </div> >') .  '</a></div>'.$next_page_title.'</div>';
          }
          elseif ($page_number == $page_count - 1) {
            $pager_bottom = '<div class="paging8 paging8-bottom"><span class="page-counter col-xs-12">Page ' . ($page_number + 1) . ' of ' . $page_count . '</span><div class="paging8-prev paging8-prev-full col-xs-6"><a href="' . $path_alias . $prev_page_arg . '" class="col-xs-12">' . $this->t('< <div class="mobile-slider"> Previous</div>') . '</a></div>'.$previous_page_title.'</div>';
          }
          else {
            $pager_bottom = '<div class="paging8 paging8-bottom"><span class="page-counter col-xs-12">Page ' . ($page_number + 1) . ' of ' . $page_count . '</span><div class="paging8-prev col-xs-6"><a href="' . $path_alias . $prev_page_arg . '" class="col-xs-12">' . $this->t('< <div class="mobile-slider"> Previous</div>') . '</a>'.$previous_page_title.'</div><div class="paging8-next col-xs-6"><a href="' . $path_alias . $next_page_arg . '" class="col-xs-12">' . $this->t('<div class="mobile-slider">Next </div> >') . '</a>'.$next_page_title.'</div></div>';
          };
        }
        else {
          $pager_bottom = '';
        }
      }

      // Renders the final result and loads style.css.
      $result = new FilterProcessResult($pager_top . $splitted . $pager_bottom . $page_title_list);

      if ($this->settings['paging8_loadcss'] == TRUE) {
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
    $form['paging8_showpagertop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show simple pager on top'),
      '#default_value' => $this->settings['paging8_showpagertop'],
      '#description' => $this->t('Display a simple prev/next pager above the content.'),
    ];
    $form['paging8_showpagerbottom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show simple pager on bottom'),
      '#default_value' => $this->settings['paging8_showpagerbottom'],
      '#description' => $this->t('Display a simple prev/next pager below the content'),
    ];
    $form['paging8_showpagetitles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show page list'),
      '#default_value' => $this->settings['paging8_showpagetitles'],
      '#description' => $this->t('Display clickable page list after the content.</br>Format: &lt;!--pagetitles--Title1||Title2--pagetitles--!&gt;.</br>Position of the list is not relevant.'),
    ];
    $form['paging8_loadcss'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load paging8.basic.css'),
      '#default_value' => $this->settings['paging8_loadcss'],
      '#description' => $this->t('Check to load the default css. If you want to customize, uncheck this option and style the pager with your theme css.'),
    ];
    return $form;
  }

}