<?php
/**
 * Plugin Name: AssociateDB Shared UI Helpers
 * Description: Shared director dashboard/page shell styles and rendering helpers.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pitblado_get_director_shared_styles' ) ) {
	function pitblado_get_director_shared_styles() {
		static $printed = false;

		if ( $printed ) {
			return '';
		}

		$printed = true;

		return '<style id="pitblado-director-shared-ui-css">
			.director-page-panel{background:#fff;border:1px solid #e7e9ef;border-radius:18px;box-shadow:0 10px 26px rgba(15,23,42,.06);padding:24px;margin:0 0 22px}
			.director-page-header-row,.director-panel-header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap}
			.director-page-title{margin:0;font-size:32px;line-height:1.15;font-weight:700;color:#0f172a}
			.director-page-subtitle,.director-panel-subtitle{margin:6px 0 0;font-size:14px;color:#667085}
			.director-panel-title{margin:0;font-size:20px;font-weight:700;color:#0f172a}
			.director-overview-actions{display:flex;flex-wrap:wrap;gap:10px}
			a.director-primary-btn,a.director-secondary-btn,a.director-danger-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;border-radius:12px;border:1px solid transparent;font-size:14px;font-weight:600;text-decoration:none;line-height:1.2;transition:all .2s ease}
			a.director-primary-btn{background:#f2497f;border-color:#f2497f;color:#fff}
			a.director-primary-btn:hover{background:#e13a70;border-color:#e13a70}
			a.director-secondary-btn{background:#f8fafc;border-color:#d0d5dd;color:#0f172a}
			a.director-secondary-btn:hover{background:#eef2f7;border-color:#c4cad5}
			a.director-danger-btn{background:#fff1f3;border-color:#ffd0d8;color:#b42318}
			a.director-danger-btn:hover{background:#ffe4e8;border-color:#ffbeca}
			.director-success-notice{padding:10px 12px;border:1px solid #b7ebc6;background:#ecfdf3;color:#067647;border-radius:10px;margin:0 0 12px}
			.director-mini-stats{margin-top:22px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
			.director-mini-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:16px;min-height:96px}
			.director-mini-stat-label{font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;color:#667085}
			.director-mini-stat-value{margin-top:8px;font-size:30px;font-weight:700;line-height:1.05;color:#111827}
			.director-mini-stat-value-small{font-size:20px}
			.director-associates-table-wrap{border:1px solid #e4e7ec;border-radius:14px;overflow:auto}
			.director-associates-table{width:100%;min-width:640px;border-collapse:separate;border-spacing:0}
			.director-associates-table th{background:#f8fafc;color:#475467;font-size:12px;letter-spacing:.02em;text-transform:uppercase;font-weight:700;text-align:left;padding:12px 14px;border-bottom:1px solid #e4e7ec}
			.director-associates-table td{padding:12px 14px;font-size:14px;color:#344054;border-bottom:1px solid #eaecf0;vertical-align:middle}
			.director-associates-table tbody tr:last-child td{border-bottom:none}
			.director-associates-table td a{color:#f2497f;font-weight:600;text-decoration:none}
			.director-associates-table td a:hover{color:#e13a70;text-decoration:underline}
			.director-plan-status{display:inline-flex;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700}
			.director-plan-status.is-submitted{background:#ecfdf3;color:#067647}
			.director-plan-status.is-missing{background:#fef3f2;color:#b42318}
			.director-associate-name{font-weight:600;color:#101828}
			.director-associate-email{font-size:12px;color:#667085}
			.director-table-actions{display:flex;gap:10px;flex-wrap:wrap}
			.director-plan-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
			.director-plan-grid>div{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:14px}
			.director-plan-text{margin-top:8px;font-size:16px;color:#111827;font-weight:600;white-space:pre-line}
			.director-empty-state{margin-top:12px;padding:14px 16px;border-radius:12px;border:1px dashed #d0d5dd;background:#f8fafc;color:#475467;font-size:14px}
			.director-panel-link{font-size:14px;font-weight:600;color:#f2497f;text-decoration:none;white-space:nowrap}
			.director-panel-link:hover{color:#e13a70;text-decoration:underline}
			@media (max-width:900px){.director-mini-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.director-plan-grid{grid-template-columns:1fr}}
			@media (max-width:640px){.director-page-panel{padding:18px;border-radius:16px}.director-page-title{font-size:27px}.director-mini-stats{grid-template-columns:1fr}a.director-primary-btn,a.director-secondary-btn,a.director-danger-btn{width:100%}}
		</style>';
	}
}
