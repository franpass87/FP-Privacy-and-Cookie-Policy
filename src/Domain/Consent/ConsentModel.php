<?php
/**
 * Consent domain model (alias).
 *
 * This file existed as a full duplicate of FP\Privacy\Consent\LogModel.
 * It is now a thin alias to avoid namespace collisions and keep a single
 * source of truth in FP\Privacy\Consent\LogModel.
 *
 * @package FP\Privacy\Domain\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 * @deprecated Use FP\Privacy\Consent\LogModel directly.
 */

namespace FP\Privacy\Domain\Consent;

use FP\Privacy\Consent\LogModel as BaseLogModel;

/**
 * Alias for the canonical LogModel.
 *
 * @deprecated Use FP\Privacy\Consent\LogModel instead.
 */
class ConsentModel extends BaseLogModel {
}
