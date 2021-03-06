<?php

namespace Drupal\site_audit\Renderer;

use Drupal\site_audit\Renderer;
use Drupal\site_audit\Check;

class Html extends Renderer {
  /**
   * Get the CSS class associated with a percentage.
   *
   * @return string
   *   Twitter Bootstrap CSS class.
   */
  public function getPercentCssClass($percent) {
    if ($percent > 80) {
      return 'success';
    }
    if ($percent > 65) {
      return 'error';
    }
    if ($percent >= 0) {
      return 'caution';
    }
    return 'info';
  }

  /**
   * Get the CSS class associated with a score.
   *
   * @return string
   *   Name of the Twitter bootstrap class.
   */
  public function getScoreCssClass($score) {
    switch ($score) {
      case Check::AUDIT_CHECK_SCORE_PASS:
        return 'success';

      case Check::AUDIT_CHECK_SCORE_WARN:
        return 'warning';

      case Check::AUDIT_CHECK_SCORE_INFO:
        return 'info';

      default:
        return 'danger';

    }
  }

  public function render($detail = FALSE) {
    $name_exploded = explode('\\', get_class($this->report));
    $id = array_pop($name_exploded);

    $ret_val = '<h2 id="' . $id . '">' . $this->report->getLabel();
    $percent = $this->report->getPercent();

    if ($percent != Check::AUDIT_CHECK_SCORE_INFO) {
      $ret_val .= ' <span class="label label-' . $this->getPercentCssClass() . '">' . $percent . '%</span>';
    }
    else {
      $ret_val .= ' <span class="label label-info">' . t('Info') . '</span>';
    }
    $ret_val .= '</h2>';

    if ($percent == 100) {
      $ret_val .= '<p class="text-success">';
      $ret_val .= '<strong>' . t('Well done!') . '</strong> ' . t('No action required.');
      $ret_val .= '</p>';
    }

    if ($detail || $percent != 100) {
      foreach ($this->report->getChecks() as $check) {
        $score = $check->getScore();
        if ($detail || $score < Check::AUDIT_CHECK_SCORE_PASS || $percent == Check::AUDIT_CHECK_SCORE_INFO) {
          $ret_val .= '<div class="panel panel-' . $this->getScoreCssClass() . '">';
          // Heading.
          $ret_val .= '<div class="panel-heading"><strong>' . $check->getLabel() . '</strong>';
          if ($detail) {
            $ret_val .= '<small> - ' . $check->getDescription() . '</small>';
          }
          $ret_val .= '</div>';

          // Result.
          $result = $check->getResult();
          // Table.
          if (is_array($result)) {
            $ret_val .= '<table>';
            $ret_val .= '<thead><tr><th>' . implode('</th><th>', $result['headers']) . '</th></tr></thead>';
            $ret_val .= '<tbody>';
            foreach ($result['rows'] as $row) {
              $ret_val .= '<tr><td>' . implode('</td><td>', $row) . '</th></td>';
            }
            $ret_val .= '</tbody>';
            $ret_val .= '</table>';
          }
          else {
            $ret_val .= '<p>' . $result . '</p>';
          }

          // Action.
          if ($check->renderAction()) {
            $ret_val .= '<div class="well well-small">' . $check->renderAction() . '</div>';
          }
          $ret_val .= '</div>';
        }
      }
    }
    $ret_val .= "\n";
    return $ret_val;
  }
}
