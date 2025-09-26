(function () {
    const storageKey = 'fpPrivacyChecklist';
    const HEX_PATTERN = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i;

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function isValidHex(value) {
        return typeof value === 'string' && HEX_PATTERN.test(value.trim());
    }

    function normalizeHex(value, fallback) {
        if (isValidHex(value)) {
            return value.trim();
        }

        return isValidHex(fallback) ? fallback.trim() : '#000000';
    }

    function hexToRgb(hex) {
        if (!isValidHex(hex)) {
            return null;
        }

        let normalized = hex.trim().slice(1);
        if (normalized.length === 3) {
            normalized = normalized.split('').map((char) => char + char).join('');
        }

        const value = parseInt(normalized, 16);

        return {
            r: (value >> 16) & 255,
            g: (value >> 8) & 255,
            b: value & 255,
        };
    }

    function rgbToHex(rgb) {
        if (!rgb) {
            return '#000000';
        }

        const r = clamp(Math.round(rgb.r || 0), 0, 255);
        const g = clamp(Math.round(rgb.g || 0), 0, 255);
        const b = clamp(Math.round(rgb.b || 0), 0, 255);

        const toHex = (component) => component.toString(16).padStart(2, '0');

        return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
    }

    function mixHexColors(first, second, amount) {
        const mixAmount = clamp(Number(amount) || 0, 0, 1);
        const firstRgb = hexToRgb(first);
        const secondRgb = hexToRgb(second);

        if (!firstRgb || !secondRgb) {
            return normalizeHex(first, '#000000');
        }

        return rgbToHex({
            r: firstRgb.r * (1 - mixAmount) + secondRgb.r * mixAmount,
            g: firstRgb.g * (1 - mixAmount) + secondRgb.g * mixAmount,
            b: firstRgb.b * (1 - mixAmount) + secondRgb.b * mixAmount,
        });
    }

    function adjustLuminance(hex, percent) {
        const amount = clamp(Number(percent) || 0, -1, 1);

        if (amount === 0) {
            return normalizeHex(hex, '#000000');
        }

        if (amount > 0) {
            return mixHexColors(hex, '#ffffff', amount);
        }

        return mixHexColors(hex, '#000000', Math.abs(amount));
    }

    function buildRgba(hex, alpha) {
        const rgb = hexToRgb(hex) || { r: 0, g: 0, b: 0 };
        const normalizedAlpha = clamp(Number(alpha) || 0, 0, 1);

        return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${normalizedAlpha.toFixed(2)})`;
    }

    function getContrastColor(hex) {
        const rgb = hexToRgb(hex);

        if (!rgb) {
            return '#ffffff';
        }

        const luminance = (0.2126 * rgb.r + 0.7152 * rgb.g + 0.0722 * rgb.b) / 255;

        return luminance > 0.55 ? '#000000' : '#ffffff';
    }

    function loadState() {
        try {
            const raw = window.localStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : {};
        } catch (err) {
            return {};
        }
    }

    function saveState(state) {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (err) {
            // ignore storage errors
        }
    }

    function initWizard() {
        const container = document.querySelector('[data-fp-onboarding]');
        if (!container) {
            return;
        }

        const steps = Array.from(container.querySelectorAll('[data-fp-step]'));
        const progressBar = container.querySelector('.fp-onboarding__progress-bar');
        const progressCount = container.querySelector('[data-fp-progress-count]');
        const startButton = container.querySelector('[data-fp-start-wizard]');
        const manualState = loadState();

        function setStepState(step, checkbox, complete) {
            if (complete) {
                step.classList.add('is-complete');
                if (checkbox) {
                    checkbox.checked = true;
                }
            } else {
                step.classList.remove('is-complete');
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        }

        function updateProgress() {
            const completed = steps.filter((step) => step.classList.contains('is-complete')).length;
            const total = steps.length || 1;
            if (progressBar) {
                progressBar.style.width = `${(completed / total) * 100}%`;
            }
            if (progressCount) {
                progressCount.textContent = String(completed);
            }
        }

        steps.forEach((step) => {
            const key = step.getAttribute('data-fp-step');
            const checkbox = step.querySelector('[data-fp-step-checkbox]');
            const isAuto = step.getAttribute('data-fp-step-auto') === '1';
            const initialComplete = step.getAttribute('data-fp-step-complete') === '1';

            if (!checkbox) {
                return;
            }

            if (isAuto) {
                setStepState(step, checkbox, initialComplete);
                checkbox.disabled = true;
            } else {
                const stored = manualState.hasOwnProperty(key) ? manualState[key] : initialComplete;
                setStepState(step, checkbox, stored);
                checkbox.addEventListener('change', () => {
                    const value = checkbox.checked;
                    setStepState(step, checkbox, value);
                    manualState[key] = value;
                    saveState(manualState);
                    updateProgress();
                });
            }
        });

        if (startButton) {
            startButton.addEventListener('click', () => {
                const next = steps.find((step) => !step.classList.contains('is-complete'));
                if (next) {
                    next.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    next.classList.add('is-active');
                    window.setTimeout(() => next.classList.remove('is-active'), 1500);
                }
            });
        }

        updateProgress();
    }

    function initBannerPreview() {
        const previewRoot = document.querySelector('[data-fp-preview-root]');
        if (!previewRoot) {
            return;
        }

        const form = document.querySelector('.fp-privacy-admin form');
        if (!form) {
            return;
        }

        const defaultLanguage = previewRoot.getAttribute('data-default-language') || 'it';
        let currentLanguage = defaultLanguage;
        const languageSelector = document.querySelector('[data-fp-preview-language-selector]');
        const previewWrapper = previewRoot;
        const layoutControl = document.querySelector('[data-fp-preview-layout-control]');
        const colorTextInputs = Array.from(document.querySelectorAll('[data-fp-color-control]'));
        const colorPickerInputs = Array.from(document.querySelectorAll('[data-fp-color-picker]'));
        const defaultColors = {
            background_color: '#ffffff',
            text_color: '#1f2933',
            accent_color: '#2563eb',
            secondary_color: '#eef2ff',
            secondary_text_color: '#1e3a8a',
            border_color: '#dbeafe',
        };
        const colorState = {};
        const textInputsMap = {};
        const colorPickersMap = {};

        Object.keys(defaultColors).forEach((key) => {
            colorState[key] = defaultColors[key];
        });

        colorTextInputs.forEach((input) => {
            const key = input.getAttribute('data-fp-color-key');
            if (!key) {
                return;
            }

            const normalized = normalizeHex(input.value, defaultColors[key] || '#000000');
            input.value = normalized;
            colorState[key] = normalized;
            textInputsMap[key] = input;
        });

        colorPickerInputs.forEach((input) => {
            const key = input.getAttribute('data-fp-color-key');
            if (!key) {
                return;
            }

            const normalized = normalizeHex(input.value, colorState[key] || defaultColors[key] || '#000000');
            input.value = normalized;
            colorState[key] = normalized;
            colorPickersMap[key] = input;
        });

        function refreshTheme() {
            const background = colorState.background_color || defaultColors.background_color;
            const text = colorState.text_color || defaultColors.text_color;
            const accent = colorState.accent_color || defaultColors.accent_color;
            const secondary = colorState.secondary_color || defaultColors.secondary_color;
            const secondaryText = colorState.secondary_text_color || defaultColors.secondary_text_color;
            const border = colorState.border_color || defaultColors.border_color;
            const manageBg = mixHexColors(background, '#ffffff', 0.75);

            previewWrapper.style.setProperty('--fp-banner-background', background);
            previewWrapper.style.setProperty('--fp-banner-text', text);
            previewWrapper.style.setProperty('--fp-banner-border', border);
            previewWrapper.style.setProperty('--fp-banner-muted-text', mixHexColors(text, background, 0.4));
            previewWrapper.style.setProperty('--fp-banner-accent', accent);
            previewWrapper.style.setProperty('--fp-banner-accent-contrast', getContrastColor(accent));
            previewWrapper.style.setProperty('--fp-banner-accent-active', adjustLuminance(accent, -0.15));
            previewWrapper.style.setProperty('--fp-banner-accent-soft', buildRgba(accent, 0.12));
            previewWrapper.style.setProperty('--fp-banner-accent-softer', buildRgba(accent, 0.18));
            previewWrapper.style.setProperty('--fp-banner-secondary-bg', secondary);
            previewWrapper.style.setProperty('--fp-banner-secondary-text', secondaryText);
            previewWrapper.style.setProperty('--fp-banner-secondary-border', mixHexColors(secondary, '#000000', 0.12));
            previewWrapper.style.setProperty('--fp-banner-secondary-hover', mixHexColors(secondary, '#ffffff', 0.2));
            previewWrapper.style.setProperty('--fp-banner-card-bg', mixHexColors(background, '#ffffff', 0.92));
            previewWrapper.style.setProperty('--fp-banner-card-border', mixHexColors(background, '#000000', 0.08));
            previewWrapper.style.setProperty('--fp-banner-switch-off', mixHexColors(accent, '#ffffff', 0.65));
            previewWrapper.style.setProperty('--fp-banner-manage-bg', manageBg);
            previewWrapper.style.setProperty('--fp-banner-manage-border', mixHexColors(background, '#000000', 0.12));
            previewWrapper.style.setProperty('--fp-banner-manage-hover-bg', mixHexColors(manageBg, '#ffffff', 0.12));
            previewWrapper.style.setProperty('--fp-banner-manage-text', mixHexColors(text, '#ffffff', 0.08));
        }

        function updateLayoutClass(value) {
            const raw = typeof value === 'string' ? value : '';
            const slug = raw ? raw.replace(/_/g, '-') : 'floating';
            previewWrapper.setAttribute('data-fp-preview-layout', slug);
            Array.from(previewWrapper.classList).forEach((cls) => {
                if (cls.indexOf('fp-preview-layout--') === 0) {
                    previewWrapper.classList.remove(cls);
                }
            });
            previewWrapper.classList.add(`fp-preview-layout--${slug}`);
        }

        colorTextInputs.forEach((input) => {
            const key = input.getAttribute('data-fp-color-key');
            if (!key) {
                return;
            }

            input.addEventListener('input', () => {
                const value = input.value.trim();
                if (!isValidHex(value)) {
                    return;
                }

                const normalized = normalizeHex(value, colorState[key] || defaultColors[key]);
                colorState[key] = normalized;
                if (colorPickersMap[key] && colorPickersMap[key].value !== normalized) {
                    colorPickersMap[key].value = normalized;
                }
                refreshTheme();
            });

            input.addEventListener('change', () => {
                const value = input.value.trim();
                if (!isValidHex(value)) {
                    const fallback = colorState[key] || defaultColors[key] || '#000000';
                    input.value = fallback;
                    if (colorPickersMap[key] && colorPickersMap[key].value !== fallback) {
                        colorPickersMap[key].value = fallback;
                    }
                    return;
                }

                const normalized = normalizeHex(value, colorState[key] || defaultColors[key]);
                colorState[key] = normalized;
                input.value = normalized;
                if (colorPickersMap[key] && colorPickersMap[key].value !== normalized) {
                    colorPickersMap[key].value = normalized;
                }
                refreshTheme();
            });
        });

        colorPickerInputs.forEach((input) => {
            const key = input.getAttribute('data-fp-color-key');
            if (!key) {
                return;
            }

            input.addEventListener('input', () => {
                const normalized = normalizeHex(input.value, colorState[key] || defaultColors[key]);
                colorState[key] = normalized;
                input.value = normalized;
                if (textInputsMap[key] && textInputsMap[key].value !== normalized) {
                    textInputsMap[key].value = normalized;
                }
                refreshTheme();
            });
        });

        if (layoutControl) {
            layoutControl.addEventListener('change', () => {
                updateLayoutClass(layoutControl.value || 'floating');
            });
        }

        updateLayoutClass(previewWrapper.getAttribute('data-fp-preview-layout') || 'floating');
        refreshTheme();

        const previewMap = {};
        previewRoot.querySelectorAll('[data-fp-preview]').forEach((el) => {
            const key = el.getAttribute('data-fp-preview');
            if (!previewMap[key]) {
                previewMap[key] = [];
            }
            previewMap[key].push(el);
        });

        const toggleTargets = {};
        previewRoot.querySelectorAll('[data-fp-toggle-target]').forEach((el) => {
            const key = el.getAttribute('data-fp-toggle-target');
            if (!toggleTargets[key]) {
                toggleTargets[key] = [];
            }
            toggleTargets[key].push(el);
        });

        const categoryMap = {};
        previewRoot.querySelectorAll('[data-fp-preview-category]').forEach((el) => {
            const key = el.getAttribute('data-fp-preview-category');
            const fields = {
                container: el,
                label: el.querySelector('[data-fp-preview-category-field="label"]'),
                description: el.querySelector('[data-fp-preview-category-field="description"]'),
                services: el.querySelector('[data-fp-preview-category-field="services"]'),
                servicesText: el.querySelector('.fp-banner-preview__services-text'),
                required: el.querySelector('[data-fp-category-element="required"]'),
                toggle: el.querySelector('[data-fp-category-element="toggle"]'),
            };
            fields.initial = {
                label: fields.label ? fields.label.textContent : '',
                description: fields.description ? fields.description.textContent : '',
                services: fields.servicesText ? fields.servicesText.textContent : '',
            };
            categoryMap[key] = fields;
        });

        let toggleTemplate = '';
        if (previewMap.toggle_aria && previewMap.toggle_aria.length) {
            toggleTemplate = previewMap.toggle_aria[0].textContent || '';
        }

        function updatePreviewText(key, value) {
            const targets = previewMap[key];
            if (!targets) {
                return;
            }

            targets.forEach((el) => {
                if (key === 'banner_message') {
                    el.innerHTML = value ? value.replace(/\n/g, '<br />') : '';
                } else if (key === 'modal_intro') {
                    el.textContent = value;
                } else {
                    el.textContent = value;
                }
            });

            if (key === 'toggle_aria') {
                toggleTemplate = value || '';
                Object.values(categoryMap).forEach((fields) => {
                    if (fields.toggle) {
                        const label = fields.toggle.getAttribute('data-fp-category-label') || '';
                        if (toggleTemplate && toggleTemplate.indexOf('%s') !== -1) {
                            fields.toggle.textContent = toggleTemplate.replace('%s', label);
                        } else {
                            fields.toggle.textContent = toggleTemplate;
                        }
                    }
                });
            }
        }

        function updateToggleVisibility(key, visible) {
            const targets = toggleTargets[key];
            if (!targets) {
                return;
            }
            targets.forEach((el) => {
                el.classList.toggle('is-hidden', !visible);
            });
        }

        function updateCategoryField(categoryKey, field, value) {
            const fields = categoryMap[categoryKey];
            if (!fields) {
                return;
            }
            if (field === 'label') {
                if (fields.label) {
                    fields.label.textContent = value;
                }
                if (fields.toggle) {
                    fields.toggle.setAttribute('data-fp-category-label', value);
                    if (toggleTemplate && toggleTemplate.indexOf('%s') !== -1) {
                        fields.toggle.textContent = toggleTemplate.replace('%s', value);
                    } else if (toggleTemplate) {
                        fields.toggle.textContent = toggleTemplate;
                    }
                }
            } else if (field === 'description' && fields.description) {
                fields.description.textContent = value;
            } else if (field === 'services') {
                if (fields.servicesText) {
                    fields.servicesText.textContent = value;
                }
                if (fields.services) {
                    fields.services.classList.toggle('is-hidden', !value);
                }
            }
        }

        function updateCategoryToggle(categoryKey, toggleType, checked) {
            const fields = categoryMap[categoryKey];
            if (!fields) {
                return;
            }
            if (toggleType === 'enabled' && fields.container) {
                fields.container.classList.toggle('is-hidden', !checked);
            }
            if (toggleType === 'required') {
                if (fields.required) {
                    fields.required.classList.toggle('is-hidden', !checked);
                }
                if (fields.toggle) {
                    fields.toggle.classList.toggle('is-hidden', checked);
                }
            }
        }

        function getFieldLanguage(element) {
            if (!element || typeof element.getAttribute !== 'function') {
                return defaultLanguage;
            }
            const language = element.getAttribute('data-fp-preview-language');
            return language || defaultLanguage;
        }

        function getLocalizedValue(language, key) {
            const selector = `[data-fp-preview-target="${key}"][data-fp-preview-language="${language}"]`;
            const fieldEl = form.querySelector(selector);
            if (fieldEl) {
                const value = fieldEl.value;
                if (typeof value === 'string') {
                    if (value.trim() !== '' || language === defaultLanguage) {
                        return value;
                    }
                }
            }
            if (language !== defaultLanguage) {
                return getLocalizedValue(defaultLanguage, key);
            }
            return '';
        }

        function getCategoryLocalizedValue(language, categoryKey, field) {
            const selector = `[data-fp-preview-language="${language}"][data-fp-preview-category="${categoryKey}"][data-fp-preview-field="${field}"]`;
            const fieldEl = form.querySelector(selector);
            if (fieldEl) {
                const value = fieldEl.value;
                if (typeof value === 'string') {
                    if (value.trim() !== '' || language === defaultLanguage) {
                        return value;
                    }
                }
            }
            if (language !== defaultLanguage) {
                return getCategoryLocalizedValue(defaultLanguage, categoryKey, field);
            }
            const fields = categoryMap[categoryKey];
            if (fields && fields.initial && Object.prototype.hasOwnProperty.call(fields.initial, field)) {
                return fields.initial[field];
            }
            return '';
        }

        function applyLanguage(language) {
            currentLanguage = language || defaultLanguage;
            Object.keys(previewMap).forEach((key) => {
                const value = getLocalizedValue(currentLanguage, key);
                updatePreviewText(key, value || '');
            });
            Object.keys(categoryMap).forEach((categoryKey) => {
                ['label', 'description', 'services'].forEach((field) => {
                    const value = getCategoryLocalizedValue(currentLanguage, categoryKey, field);
                    updateCategoryField(categoryKey, field, value || '');
                });
            });
        }

        form.addEventListener('input', (event) => {
            const target = event.target;
            const previewKey = target.getAttribute('data-fp-preview-target');
            if (previewKey) {
                const targetLanguage = getFieldLanguage(target);
                if (targetLanguage === currentLanguage) {
                    updatePreviewText(previewKey, target.value || '');
                }
            }

            const categoryKey = target.getAttribute('data-fp-preview-category');
            const categoryField = target.getAttribute('data-fp-preview-field');
            if (categoryKey && categoryField) {
                const targetLanguage = getFieldLanguage(target);
                if (targetLanguage === currentLanguage) {
                    updateCategoryField(categoryKey, categoryField, target.value || '');
                }
            }
        });

        form.addEventListener('change', (event) => {
            const target = event.target;
            const toggleKey = target.getAttribute('data-fp-preview-toggle');
            if (toggleKey) {
                updateToggleVisibility(toggleKey, target.checked);
            }

            const categoryKey = target.getAttribute('data-fp-preview-category');
            const toggleType = target.getAttribute('data-fp-preview-category-toggle');
            if (categoryKey && toggleType) {
                updateCategoryToggle(categoryKey, toggleType, target.checked);
            }
        });

        if (languageSelector) {
            languageSelector.addEventListener('change', (event) => {
                applyLanguage(event.target.value || defaultLanguage);
            });
        }

        applyLanguage(currentLanguage);
    }

    document.addEventListener('DOMContentLoaded', () => {
        initWizard();
        initBannerPreview();
    });
})();
