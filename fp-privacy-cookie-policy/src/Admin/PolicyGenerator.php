<?php
/**
 * Policy generator.
 *
 * @package FP\Privacy\Admin
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\View;

/**
 * Generates policy contents based on detected services and options.
 */
class PolicyGenerator {
/**
 * Options handler.
 *
 * @var Options
 */
private $options;

/**
 * Detector registry.
 *
 * @var DetectorRegistry
 */
private $detector;

/**
 * View renderer.
 *
 * @var View
 */
private $view;

/**
 * Constructor.
 *
 * @param Options          $options  Options.
 * @param DetectorRegistry $detector Detector.
 * @param View             $view     View renderer.
 */
public function __construct( Options $options, DetectorRegistry $detector, View $view ) {
$this->options  = $options;
$this->detector = $detector;
$this->view     = $view;
}

/**
 * Generate privacy policy HTML.
 *
 * @param string $lang Language.
 *
 * @return string
 */
public function generate_privacy_policy( $lang ) {
return $this->view->render(
'privacy-policy.php',
array(
'lang'     => $lang,
'options'  => $this->options->all(),
'groups'   => $this->group_services(),
)
);
}

/**
 * Generate cookie policy HTML.
 *
 * @param string $lang Language.
 *
 * @return string
 */
public function generate_cookie_policy( $lang ) {
return $this->view->render(
'cookie-policy.php',
array(
'lang'     => $lang,
'options'  => $this->options->all(),
'groups'   => $this->group_services(),
)
);
}

/**
 * Get grouped services.
 *
 * @return array<string, array<int, array<string, mixed>>>
 */
public function group_services() {
$services = $this->detector->detect_services();
$groups   = array();

foreach ( $services as $service ) {
$category = $service['category'];
if ( ! isset( $groups[ $category ] ) ) {
$groups[ $category ] = array();
}
$groups[ $category ][] = $service;
}

return $groups;
}

/**
 * Export snapshot of services.
 *
 * @return array<int, array<string, mixed>>
 */
public function snapshot() {
return $this->detector->detect_services();
}
}
