<?php
  /**
  * Projekt: modified eCommerce Shopsoftware
  * Modul: BX Modified Affiliates
  * Datei: admin/includes/extra/css/bx_modified_affiliates.php
  *
  * Datei-Header:
  * - Bindet modulbezogene CSS-Regeln fuer die Admin-Seite `bx_modified_affiliates.php` ein.
  * - Ausgabe erfolgt nur, wenn die aktuelle Seite `bx_modified_affiliates.php` ist.
  *
  * @package    BX_Modified_Affiliates
  * @author     Axel Benkert <info@bx-coding.de>
  * @copyright  (c) 2026
  * @version    1.0.0
  * @since      2026-08-23
  */

  defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

  if (basename($_SERVER['PHP_SELF']) == 'bx_modified_affiliates.php') {
?>
<!--// BX CSS- Framework//-->
<style>
/**
 * BX-Admin-UI v1.0
 * Lightweight Admin Component & Grid Framework
 */


/**
 * Standard-CSS erweitern
 */
.error_message {
  margin: 0.5rem 0;
  border-radius: var(--bx-radius);
  box-sizing: border-box;
}


/* ==========================================================================
   1. TOKENS & VARIABLES
   ========================================================================== */
:root {
  --bx-primary: #AF417E;
  --bx-primary-hover: #d34e97;
  --bx-secondary: #a7b800;
  --bx-secondary-hover: #c1d201;
  --bx-white: #ffffff;
  --bx-bg-light-pink: #fef2f2;
  --bx-bg-input: #f8fafc;
  --bx-bg-card: #eceff3;
  --bx-text-main: #0f172a;
  --bx-text-muted: #64748b;
  --bx-border-color: #cbd5e1;
  --bx-border-accent: #c41e3a;
  --bx-radius: 6px;
  --bx-shadow-lg: 0 18px 30px -14px rgba(15, 23, 42, 0.25);
  --bx-gradient-primary: linear-gradient(180deg, rgb(195, 101, 152) 0%, rgb(175, 65, 126) 55%, rgb(146, 46, 102) 100%);
  --bx-gradient-secondary: linear-gradient(180deg, rgb(193, 210, 1) 0%, rgb(167, 184, 0) 55%, rgb(139, 156, 0) 100%);
}

html {
  scrollbar-gutter: stable;
}

/* ==========================================================================
   2. LAYOUT ENGINE (Robustes 2-Spalten Layout für Admin-Panels)
   ========================================================================== */
.bx-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 480px);
  gap: 0.25rem;
  align-items: start;
  margin: 0.5rem 0;
  width: 100%;
  box-sizing: border-box;
}

.bx-main-content,
.bx-sidebar {
  min-width: 0;
  max-width: 100%;
  margin: 0.25rem;
}

@media (max-width: 1024px) {
  .bx-grid {
    grid-template-columns: 1fr;
  }
  .bx-sidebar {
    max-width: 100%;
    margin: 0.25rem;
  }
}

/* ==========================================================================
   3. COMPONENTS
   ========================================================================== */

/* Headboard / Sektions-Header */
.bx-headboard {
  display: flex; 
  flex-direction: row; 
  justify-content: flex-start;
  align-items: center;
  border-radius: var(--bx-radius);
  box-shadow: var(--bx-shadow-lg);
  margin-bottom: 0.25rem; 
  min-height: 40px;
  line-height: 30px;
  box-sizing: border-box;
  background: var(--bx-gradient-primary);
  color: var(--bx-white);
  padding: 5px 10px;
}

.bx-headboard--secondary {
  background: var(--bx-gradient-secondary);
}

/* ==========================================================================
   HEADBOARD SEARCH EXTENSION
   ========================================================================== */

/* Headboard Flex-Container anpassen */
.bx-headboard--with-search {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  padding: 4px 10px; /* Schlankes Padding, verhindert Aufblähen */
  gap: 1rem;
}

.bx-headboard-title {
  white-space: nowrap;
}

/* Suchbereich-Container */
.bx-headboard-search form {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 0.35rem;
  margin: 0;
  padding: 0;
}

/* Kompaktes Input-Feld für Header/Headboards */
.bx-input-compact {
  height: 28px;
  padding: 2px 8px;
  font-size: 0.85rem;
  border: 1px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  background-color: var(--bx-white);
  color: var(--bx-text-main);
  box-sizing: border-box;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.bx-input-compact:focus-visible {
  border-color: var(--bx-white);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
}

/* Icon-Button ohne störende Ränder/Margins */
.bx-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  padding: 0;
  margin: 0;
  cursor: pointer;
  line-height: 1;
  opacity: 0.9;
  transition: opacity 0.2s ease, transform 0.1s ease;
}

.bx-icon-btn:hover {
  opacity: 1;
  transform: scale(1.08);
  background-color: transparent;
}

/* Barrierefreie Klasse für Screenreader (versteckt das Label visuell) */
.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Container / Panels */
.bx-panel {
  box-sizing: border-box;
  background: var(--bx-white);
  border: 1px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  box-shadow: var(--bx-shadow-sm);
  padding: 1.0rem;
  margin: 0.5rem auto;
}

/* Akzentuierte Panels - Erstmal ausblenden */
.bx-panel--accent {
  border-left: 3px solid var(--bx-primary);
}

.bx-panel-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--bx-primary);
  margin-bottom: 0.5rem;
}

/* Formulare & Eingabefelder */
.bx-form {
  display: block;
  box-sizing: border-box;
  width: 100%;
  margin: 0.5rem 0;
  padding: 1rem;
  background: var(--bx-white);
  border: 1px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  box-shadow: var(--bx-shadow-sm);
  color: var(--bx-text-main);
}

.bx-form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}

.bx-form-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--bx-text-main);
}

.bx-input,
.bx-textarea,
.bx-select {
  width: 100%;
  padding: 0.75rem 1rem !important;
  font-size: 1rem;
  border: 1.5px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  background-color: var(--bx-bg-input);
  transition: all 0.2s ease-in-out;
  box-sizing: border-box;
  color: var(--bx-text-main);
}

.bx-input:focus-visible,
.bx-textarea:focus-visible,
.bx-select:focus-visible {
  outline: none;
  border-color: var(--bx-primary);
  background-color: var(--bx-white);
  box-shadow: 0 0 0 4px rgba(175, 65, 126, 0.15);
}

.bx-input:user-invalid,
.bx-textarea:user-invalid {
  border-color: var(--bx-border-accent);
  background-color: var(--bx-bg-light-pink);
}

/* Custom Select Styling */
.bx-select-wrapper {
  position: relative;
  width: 100%;
}

.bx-select {
  padding-right: 2.5rem;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  cursor: pointer;
}

.bx-select-wrapper::after {
  content: "";
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  width: 0.8rem;
  height: 0.8rem;
  pointer-events: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
  background-size: contain;
  background-repeat: no-repeat;
  transition: transform 0.2s ease;
}

.bx-select-wrapper:focus-within::after {
  transform: translateY(-50%) rotate(180deg);
}

/* Buttons */
.bx-btn {
  display: inline-block;
  width: 100%;
  padding: 0.875rem;
  font-size: 1rem;
  font-weight: 600;
  text-align: center;
  color: var(--bx-white);
  background-color: var(--bx-primary);
  border: none;
  border-radius: var(--bx-radius);
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.bx-btn:hover {
  background-color: var(--bx-primary-hover);
}

.bx-form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.bx-form-actions .bx-btn,
.bx-form-actions .bx-btn--secondary {
  width: auto;
  flex: 1;
}

.bx-btn--secondary {
  display: inline-block;
  padding: 0.875rem;
  font-size: 1rem;
  font-weight: 600;
  text-align: center;
  text-decoration: none;
  color: var(--bx-primary);
  background-color: transparent;
  border: 1.5px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.bx-btn--secondary:hover {
  background-color: var(--bx-bg-input);
}

/* Accordion Card (<details> Modul) */
.bx-card {
  position: relative;
  border: 1px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  background: var(--bx-gradient-primary);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
  margin: 5px 0;
  overflow: hidden;
}

.bx-card-summary {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  color: var(--bx-white);
  font-weight: bold;
}

.bx-card-summary::-webkit-details-marker { display: none; }

.bx-card-body {
  padding: 12px;
  background: var(--bx-bg-card);
  border-left: 4px solid var(--bx-border-accent);
  color: var(--bx-text-main);
}


/* ==========================================================================
   4. ADVANCED HTML5 COMPONENTS
   ========================================================================== */

/* Native Modal Dialoge (<dialog>) */
.bx-dialog {
  border: none;
  border-radius: var(--bx-radius);
  padding: 1.5rem;
  background: var(--bx-white);
  box-shadow: var(--bx-shadow-lg);
  max-width: 500px;
  width: 90%;
}

.bx-dialog::backdrop {
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
}

/* Fieldset & Legend Gruppierung */
.bx-fieldset {
  border: 1px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  padding: 1rem 1.25rem 1.25rem;
  margin-bottom: 1.25rem;
  background: var(--bx-white);
}

.bx-legend {
  font-weight: 700;
  font-size: 0.875rem;
  color: var(--bx-primary);
  padding: 0 0.5rem;
}

/* Semantischer Such-Container (<search>) */
.bx-search-bar {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

/* Progress & Meter Bars */
.bx-progress, 
.bx-meter {
  width: 100%;
  height: 0.75rem;
  border-radius: var(--bx-radius);
  overflow: hidden;
  appearance: none;
  border: none;
}

.bx-progress::-webkit-progress-bar { background-color: var(--bx-bg-input); }
.bx-progress::-webkit-progress-value { background-color: var(--bx-primary); }
.bx-progress::-moz-progress-bar { background-color: var(--bx-primary); }

/* Text Highlighting (<mark>) */
.bx-highlight {
  background-color: #fef08a;
  color: var(--bx-text-main);
  padding: 0.1rem 0.3rem;
  border-radius: 3px;
}

/* ==========================================================================
   MODERNE LISTEN (UL / LI)
   ========================================================================== */

.bx-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.bx-list li {
  position: relative;
  padding-left: 1.5rem;
  margin-bottom: 0.5rem;
  color: var(--bx-text-main);
  line-height: 1.5;
}

.bx-list li:last-child {
  margin-bottom: 0;
}

/* Custom Minimal Bullet Point */
.bx-list li::before {
  content: "";
  position: absolute;
  left: 0.35rem;
  top: 0.55em;
  width: 6px;
  height: 6px;
  background-color: var(--bx-primary);
  border-radius: 50%;
}

.bx-list li strong {
  color: var(--bx-text-main);
}

/* Optionale Card-Listen-Variante für Einstellungsübersichten */
.bx-list-cards {
  list-style: none;
  padding: 0;
  margin: 0 0 1.25rem 0;
}

.bx-list-cards li {
  background: var(--bx-white);
  border: 1px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  padding: 0.65rem 1rem;
  margin-bottom: 0.5rem;
  box-shadow: var(--bx-shadow-sm);
}
/* ==========================================================================
   DEFINITIONSLISTEN (DL / DT / DD)
   ========================================================================== */

/* Zweispaltige Formular- & Stammdatenübersicht */
.bx-dl-horizontal {
  margin: 0 0 0.5rem 0;
  padding: 0;
  background: var(--bx-white);
  border: 1px solid var(--bx-border-color);
  border-radius: var(--bx-radius);
  overflow: hidden;
}

.bx-dl-item {
  display: flex;
  border-bottom: 1px solid #f1f5f9;
}

.bx-dl-item:last-child {
  border-bottom: none;
}

.bx-dl-horizontal dt {
  flex: 0 0 35%;
  padding: 0.65rem 1rem;
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--bx-text-muted);
  background-color: var(--bx-bg-input);
  border-right: 1px solid #f1f5f9;
}

.bx-dl-horizontal dd {
  flex: 1;
  padding: 0.65rem 1rem;
  margin: 0;
  color: var(--bx-text-main);
  font-size: 0.875rem;
}

/* Card-Formatierung für KPI-Widgets & Sidebar-Details */
.bx-dl-cards {
  margin: 0;
  padding: 0;
}

.bx-dl-card-item {
  background: var(--bx-white);
  border: 1px solid var(--bx-border-color);
  border-left: 4px solid var(--bx-primary);
  border-radius: var(--bx-radius);
  padding: 0.75rem 1rem;
  margin-bottom: 0.65rem;
  box-shadow: var(--bx-shadow-sm);
}

.bx-dl-cards dt {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--bx-text-muted);
  margin-bottom: 0.25rem;
}

.bx-dl-cards dd {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--bx-text-main);
}

.bx-dl-cards .bx-dl-card-item:last-child {
  margin: 0;
}
</style>

<style>
.bx-affiliates-panel {
  margin: 6px 10px;
  padding: 12px 12px 10px 12px !important;
  background: #fdfdfd;
  border: 1px solid #d9d9d9;
  border-left: 3px solid #af417e;
  border-radius: 4px;
  box-shadow: inset 0 1px 0 #ffffff;
}

.bx-affiliates-table {
  width: calc(100% - 0px);
  margin: 6px 0;
  border: 1px solid #d9d9d9;
  border-left: 3px solid #af417e;
  border-radius: 4px;
  border-spacing: 0;
  background: #fdfdfd;
  box-shadow: var(--bx-shadow-lg);
  color: #444444;
}

.bx-affiliates-table th,
.bx-affiliates-table td {
  padding: 0 10px;
  border-bottom: 1px solid #e7e7e7;
  text-align: left;
}

.bx-affiliates-table th {
  background: #f3f3f3;
  color: #5a5a5a;
  font-weight: bold;
  white-space: nowrap;
}
.bx-affiliates-table th .container {
  display: inline-flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: flex-start;
  align-items: center;
  align-content: flex-start;
  gap: 4px;
}

.boxRight .container {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(1, 1fr);
  gap: 2px;
}

.bx-affiliates-table tr:last-child td {
  border-bottom: 0;
}

.bx-affiliates-table tbody tr:hover td,
.bx-affiliates-table tr:not(:first-child):hover td {
  background: #fff7fb;
  cursor: pointer;
}

.bx-affiliates-table tbody tr.bx-affiliates-selected td,
.bx-affiliates-table tr:not(:first-child).bx-affiliates-selected td {
  background: var(--bx-bg-light-pink);
  cursor: pointer;
}

.tree-branch {
  font-family: monospace; 
  white-space: pre;
  font-size: 1.2rem;
  font-weight: bold;
  color: var(--bx-primary);
  margin-left: 1rem;
}

.bx_svg_icon {
  font-size: 1.4rem;
  transition: all 0.2s ease-in-out;
}
.bx_svg_icon:hover {
  transform: scale(1.1);
}

button.but_green {
  margin: 0 !important;
  color:#ddd;
  background-color: var(--bx-secondary);
  border-color: var(--bx-secondary);
}

button.but_green:hover {
  color:#fff;
  background-color: var(--bx-secondary-hover);
  border-color: var(--bx-secondary-hover);
  text-decoration:none !important;
  cursor:pointer;
}
</style>
<?php
  }
?>