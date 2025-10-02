# Overview

Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.

## What it does

- Presents an accessible, multilingual consent banner with shortcode, block, and automatic placements.
- Detects common tracking and marketing services to generate localized privacy and cookie policy documents.
- Stores consent events in a dedicated log with hashed IP addresses, retention cleanup, CSV export, and analytics summaries.
- Bridges Google Consent Mode v2 with `dataLayer` pushes and the `fp-consent-change` event so downstream scripts stay in sync.
- Offers REST and WP-CLI interfaces plus developer hooks for full automation across single and multisite environments.

## Who it is for

- Site owners who need a compliant consent workflow without external SaaS dependencies.
- Agencies rolling out privacy tooling across multisite or multi-language projects.
- Developers who want a hook-rich, build-step-free codebase they can extend or embed into custom themes.

## Requirements

- WordPress 6.2 or higher.
- PHP 7.4 or higher.
- Optional: WP-CLI for command-line management.
