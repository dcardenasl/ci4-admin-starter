var _app = (() => {
  // src/js/utils/lucide.js
  var renderLucideIcons = () => {
    if (!window.lucide || typeof window.lucide.createIcons !== "function") {
      return false;
    }
    window.lucide.createIcons({
      attrs: {
        "stroke-width": 1.8
      }
    });
    return true;
  };
  var bootLucideIcons = () => {
    if (renderLucideIcons()) {
      return;
    }
    let attempts = 0;
    const interval = setInterval(() => {
      attempts += 1;
      if (renderLucideIcons() || attempts >= 20) {
        clearInterval(interval);
      }
    }, 150);
  };

  // src/js/utils/dev.js
  var isDev = String(document.documentElement.dataset.env || "").toLowerCase() === "development";
  var devError = (...args) => {
    if (isDev) console.error(...args);
  };

  // src/js/utils/slug.js
  var slugify = (value) => String(value || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").replace(/-{2,}/g, "-").slice(0, 255);
  var bootSlugFields = () => {
    document.querySelectorAll("input[data-slug-source]").forEach((slugInput) => {
      if (!(slugInput instanceof HTMLInputElement)) {
        return;
      }
      const sourceSelector = slugInput.dataset.slugSource || "";
      const sourceInput = sourceSelector === "" ? null : document.querySelector(sourceSelector);
      const regenerateButton = slugInput.closest("[data-slug-field]")?.querySelector("[data-slug-regenerate]");
      const checkUrl = slugInput.dataset.slugCheckUrl || "";
      const currentId = slugInput.dataset.slugCurrentId || "";
      const statusIcons = slugInput.closest("[data-slug-field]")?.querySelectorAll("[data-slug-status]") || [];
      if (!(sourceInput instanceof HTMLInputElement)) {
        return;
      }
      let manual = slugInput.value.trim() !== "" && slugInput.value.trim() !== slugify(sourceInput.value);
      let availabilityTimer = 0;
      let availabilityRequest = null;
      const showStatus = (status) => {
        statusIcons.forEach((icon) => {
          if (!(icon instanceof HTMLElement)) {
            return;
          }
          const active = icon.dataset.slugStatus === status;
          icon.classList.toggle("hidden", !active);
          icon.classList.toggle("flex", active);
        });
      };
      const checkAvailability = () => {
        window.clearTimeout(availabilityTimer);
        if (availabilityRequest !== null) {
          availabilityRequest.abort();
          availabilityRequest = null;
        }
        const slug = slugInput.value.trim();
        if (checkUrl === "" || slug.length < 2 || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
          showStatus("");
          slugInput.setCustomValidity("");
          return;
        }
        availabilityTimer = window.setTimeout(() => {
          const url = new URL(checkUrl, window.location.origin);
          url.searchParams.set("slug", slug);
          if (currentId !== "") {
            url.searchParams.set("current_id", currentId);
          }
          const controller = new AbortController();
          availabilityRequest = controller;
          showStatus("checking");
          fetch(url, {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
            signal: controller.signal
          }).then((response) => response.ok ? response.json() : Promise.reject(new Error(String(response.status)))).then((payload) => {
            const available = payload && payload.available === true;
            showStatus(available ? "available" : "unavailable");
            if (available) {
              slugInput.setCustomValidity("");
              return;
            }
            const unavailableIcon = Array.from(statusIcons).find((icon) => icon instanceof HTMLElement && icon.dataset.slugStatus === "unavailable");
            slugInput.setCustomValidity(unavailableIcon instanceof HTMLElement ? unavailableIcon.title : "Slug unavailable");
          }).catch((error) => {
            if (error && error.name === "AbortError") {
              return;
            }
            showStatus("");
            slugInput.setCustomValidity("");
            devError("Slug availability check failed", error);
          }).finally(() => {
            if (availabilityRequest === controller) {
              availabilityRequest = null;
            }
          });
        }, 350);
      };
      const syncFromSource = () => {
        if (manual) {
          return;
        }
        slugInput.value = slugify(sourceInput.value);
        checkAvailability();
      };
      sourceInput.addEventListener("input", syncFromSource);
      slugInput.addEventListener("input", () => {
        const normalized = slugify(slugInput.value);
        manual = normalized !== "" && normalized !== slugify(sourceInput.value);
        slugInput.value = normalized;
        checkAvailability();
      });
      if (regenerateButton instanceof HTMLButtonElement) {
        regenerateButton.addEventListener("click", () => {
          manual = false;
          syncFromSource();
          slugInput.focus();
        });
      }
      syncFromSource();
      checkAvailability();
    });
  };

  // src/js/components/submitGuard.js
  var OVERLAY_ID = "global-submit-guard-overlay";
  var SAFETY_TIMEOUT_MS = 15e3;
  function isMutatingForm(form) {
    if (!(form instanceof HTMLFormElement)) {
      return false;
    }
    if (form.hasAttribute("data-no-submit-guard")) {
      return false;
    }
    const method = (form.getAttribute("method") || "get").toLowerCase();
    return method !== "get";
  }
  function showOverlay() {
    if (document.getElementById(OVERLAY_ID)) {
      return;
    }
    const overlay = document.createElement("div");
    overlay.id = OVERLAY_ID;
    overlay.className = "fixed inset-0 z-40 cursor-not-allowed bg-black/30";
    overlay.setAttribute("aria-hidden", "true");
    document.body.appendChild(overlay);
  }
  function hideOverlay() {
    document.getElementById(OVERLAY_ID)?.remove();
  }
  function disableSubmitControls(form) {
    const controls = form.querySelectorAll(
      'button[type="submit"], input[type="submit"], button:not([type])'
    );
    controls.forEach((control) => {
      if (control instanceof HTMLButtonElement || control instanceof HTMLInputElement) {
        control.disabled = true;
        control.dataset.submitGuardDisabled = "1";
      }
    });
  }
  function releaseGuardedControls() {
    document.querySelectorAll('[data-submit-guard-disabled="1"]').forEach((control) => {
      if (control instanceof HTMLButtonElement || control instanceof HTMLInputElement) {
        control.disabled = false;
      }
      control.removeAttribute("data-submit-guard-disabled");
    });
  }
  function bootGlobalSubmitGuard() {
    if (document.documentElement.dataset.submitGuardBooted === "1") {
      return;
    }
    document.documentElement.dataset.submitGuardBooted = "1";
    document.addEventListener("submit", (event) => {
      const form = event.target;
      if (!isMutatingForm(form) || event.defaultPrevented) {
        return;
      }
      disableSubmitControls(form);
      showOverlay();
      setTimeout(() => {
        hideOverlay();
        releaseGuardedControls();
      }, SAFETY_TIMEOUT_MS);
    });
    window.addEventListener("pageshow", (event) => {
      if (event.persisted) {
        hideOverlay();
        releaseGuardedControls();
      }
    });
  }

  // src/js/utils/labels.js
  var localePrefix = () => String(document.documentElement?.lang || "es").toLowerCase().startsWith("en") ? "en" : "es";
  var localeTag = () => localePrefix() === "en" ? "en-US" : "es-ES";
  var focusableSelector = 'a[href], button:not([disabled]), textarea, input:not([type="hidden"]):not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
  var defaultUiLabels = {
    es: {
      confirmAction: "Confirmar acci\xF3n",
      confirm: "Confirmar",
      requestFailed: "La solicitud fall\xF3 (HTTP {status}).",
      loadRetry: "No se pudo cargar la informaci\xF3n. Intenta nuevamente."
    },
    en: {
      confirmAction: "Confirm action",
      confirm: "Confirm",
      requestFailed: "Request failed (HTTP {status}).",
      loadRetry: "Could not load the information. Please try again."
    }
  };
  var defaultStatusLabels = {
    es: {
      active: "Activo",
      pending: "Pendiente",
      pending_approval: "Pendiente de aprobacion",
      suspended: "Suspendido",
      approved: "Aprobado",
      rejected: "Rechazado",
      processing: "Procesando",
      success: "Exitoso",
      failed: "Fallido"
    },
    en: {
      active: "Active",
      pending: "Pending",
      pending_approval: "Pending approval",
      suspended: "Suspended",
      approved: "Approved",
      rejected: "Rejected",
      processing: "Processing",
      success: "Success",
      failed: "Failed"
    }
  };
  var defaultAuditActionLabels = {
    es: {
      create: "Crear",
      update: "Actualizar",
      delete: "Eliminar",
      login: "Iniciar sesion",
      login_success: "Inicio de sesion exitoso",
      login_failure: "Inicio de sesion fallido",
      logout: "Cerrar sesion",
      approve: "Aprobar"
    },
    en: {
      create: "Create",
      update: "Update",
      delete: "Delete",
      login: "Login",
      login_success: "Login Success",
      login_failure: "Login Failure",
      logout: "Logout",
      approve: "Approve"
    }
  };
  var defaultAuditResultLabels = {
    es: {
      success: "Exito",
      failure: "Fallo",
      denied: "Denegado"
    },
    en: {
      success: "Success",
      failure: "Failure",
      denied: "Denied"
    }
  };
  var defaultAuditSeverityLabels = {
    es: {
      info: "Info",
      warning: "Advertencia",
      critical: "Critico"
    },
    en: {
      info: "Info",
      warning: "Warning",
      critical: "Critical"
    }
  };
  var defaultPaginationLabels = {
    es: {
      visibleResults: "Resultados visibles",
      showing: "Mostrando",
      of: "de"
    },
    en: {
      visibleResults: "Visible results",
      showing: "Showing",
      of: "of"
    }
  };
  var uiLabels = window.uiLabels || defaultUiLabels;
  var statusLabels = window.statusLabels || defaultStatusLabels;
  var auditActionLabels = window.auditActionLabels || defaultAuditActionLabels;
  var auditResultLabels = window.auditResultLabels || defaultAuditResultLabels;
  var auditSeverityLabels = window.auditSeverityLabels || defaultAuditSeverityLabels;
  var paginationLabels = window.paginationLabels || defaultPaginationLabels;
  var statusLabel = (status) => {
    const value = String(status || "").trim();
    if (value === "") {
      return "-";
    }
    const key = value.toLowerCase();
    const locale = localePrefix();
    return statusLabels[locale]?.[key] || value;
  };
  var auditActionLabel = (action) => {
    const value = String(action || "").trim();
    if (value === "") {
      return "-";
    }
    const key = value.toLowerCase();
    const locale = localePrefix();
    return auditActionLabels[locale]?.[key] || value;
  };
  var auditResultLabel = (result) => {
    const value = String(result || "").trim();
    if (value === "") {
      return "-";
    }
    const key = value.toLowerCase();
    const locale = localePrefix();
    return auditResultLabels[locale]?.[key] || value;
  };
  var auditSeverityLabel = (severity) => {
    const value = String(severity || "").trim();
    if (value === "") {
      return "-";
    }
    const key = value.toLowerCase();
    const locale = localePrefix();
    return auditSeverityLabels[locale]?.[key] || value;
  };

  // src/js/stores/confirm.store.js
  var confirmStore = () => {
    const text = uiLabels[localePrefix()] || uiLabels.es;
    return {
      open: false,
      accepting: false,
      title: text.confirmAction,
      message: "",
      onAccept: null,
      show(message, onAccept, title = text.confirmAction) {
        this.open = true;
        this.accepting = false;
        this.message = message;
        this.title = title;
        this.onAccept = onAccept;
        requestAnimationFrame(() => {
          const dialog = document.getElementById("confirm-dialog-panel");
          if (!(dialog instanceof HTMLElement)) {
            return;
          }
          const focusable = dialog.querySelector(focusableSelector);
          if (focusable instanceof HTMLElement) {
            focusable.focus();
            return;
          }
          dialog.focus();
        });
      },
      close() {
        this.open = false;
        this.accepting = false;
        this.message = "";
        this.onAccept = null;
      },
      accept() {
        this.accepting = true;
        if (typeof this.onAccept === "function") {
          this.onAccept();
        }
        const self = this;
        setTimeout(() => {
          if (self.accepting) {
            self.close();
          }
        }, 5e3);
      },
      handleTab(event, container) {
        if (!(container instanceof HTMLElement)) {
          return;
        }
        const focusable = Array.from(container.querySelectorAll(focusableSelector)).filter((element) => element instanceof HTMLElement && !element.hasAttribute("disabled"));
        if (focusable.length === 0) {
          container.focus();
          return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement;
        if (event.shiftKey && active === first) {
          event.preventDefault();
          last.focus();
          return;
        }
        if (!event.shiftKey && active === last) {
          event.preventDefault();
          first.focus();
        }
      }
    };
  };

  // src/js/stores/toast.store.js
  var toastStore = {
    items: [],
    push(type, message) {
      const id = Date.now() + Math.random();
      this.items.push({ id, type, message });
      setTimeout(() => {
        this.remove(id);
      }, 5e3);
    },
    remove(id) {
      this.items = this.items.filter((item) => item.id !== id);
    }
  };

  // src/js/utils/url.js
  var queryToObject = (search) => {
    const params = new URLSearchParams(search);
    const query = {};
    params.forEach((value, key) => {
      const trimmed = value.trim();
      if (trimmed !== "") {
        query[key] = trimmed;
      }
    });
    return query;
  };
  var objectToQueryString = (query) => {
    const params = new URLSearchParams();
    Object.entries(query || {}).forEach(([key, value]) => {
      if (typeof value === "string" && value.trim() !== "") {
        params.append(key, value.trim());
      }
    });
    return params.toString();
  };
  var formToQuery = (form) => {
    const formData = new FormData(form);
    const query = {};
    formData.forEach((value, key) => {
      if (typeof value !== "string") {
        return;
      }
      const trimmed = value.trim();
      if (trimmed !== "") {
        query[key] = trimmed;
      }
    });
    return query;
  };
  var isObject = (value) => value !== null && typeof value === "object" && !Array.isArray(value);
  var tablePayloadRoot = (payload) => {
    if (Array.isArray(payload)) {
      return { data: payload };
    }
    if (!isObject(payload)) {
      return {};
    }
    const nested = payload.data;
    if (!isObject(nested)) {
      return payload;
    }
    if (Array.isArray(nested.data) || isObject(nested.meta) || nested.current_page !== void 0 || nested.page !== void 0 || nested.last_page !== void 0 || nested.total_items !== void 0 || isObject(nested.summary)) {
      return nested;
    }
    return payload;
  };

  // src/js/stores/filePicker.store.js
  var filePickerStore = {
    open: false,
    activeTab: "library",
    files: [],
    loading: false,
    error: false,
    errorMessage: "",
    search: "",
    _searchDebounce: null,
    filterType: "",
    showFilterTabs: true,
    thumbSize: 120,
    multiSelect: false,
    selected: [],
    pagination: {
      current_page: 1,
      last_page: 1,
      total_items: 0,
      per_page: 24
    },
    dragging: false,
    uploading: false,
    uploadProgress: 0,
    uploadFileName: "",
    uploadError: "",
    _uploadFile: null,
    inputAccept: "",
    _onSelect: null,
    _onSelectMulti: null,
    show(options = {}) {
      this.open = true;
      this.multiSelect = Boolean(options.multi);
      this.showFilterTabs = options.showFilterTabs !== false;
      this.filterType = String(options.filterType || "");
      this.inputAccept = String(options.accept || "");
      this._onSelect = typeof options.onSelect === "function" ? options.onSelect : null;
      this._onSelectMulti = typeof options.onSelectMulti === "function" ? options.onSelectMulti : null;
      this.activeTab = "library";
      this.search = "";
      this.selected = [];
      this.uploadFileName = "";
      this.uploadError = "";
      this.uploading = false;
      this.uploadProgress = 0;
      this._uploadFile = null;
      this.files = [];
      this.loadFiles(1);
      requestAnimationFrame(() => {
        const panel = document.getElementById("file-picker-panel");
        if (panel instanceof HTMLElement) {
          panel.focus();
        }
      });
    },
    close() {
      this.open = false;
      this._onSelect = null;
      this._onSelectMulti = null;
    },
    switchTab(tab) {
      this.activeTab = tab;
      if (tab === "library" && this.files.length === 0) {
        this.loadFiles(1);
      }
    },
    setSearch(value) {
      this.search = String(value || "");
      clearTimeout(this._searchDebounce);
      this._searchDebounce = setTimeout(() => {
        this.loadFiles(1);
      }, 350);
    },
    setFilterType(type) {
      this.filterType = String(type || "");
      this.loadFiles(1);
    },
    changePage(page) {
      const bounded = Math.max(1, Math.min(this.pagination.last_page || 1, page));
      if (bounded !== this.pagination.current_page) {
        this.loadFiles(bounded);
      }
    },
    _panel() {
      return document.getElementById("file-picker-panel");
    },
    async loadFiles(page = 1) {
      this.loading = true;
      this.error = false;
      this.errorMessage = "";
      const panel = this._panel();
      const dataUrl = String(panel?.dataset?.dataUrl || "/files/picker-data");
      const params = new URLSearchParams({
        page: String(page),
        per_page: String(this.pagination.per_page || 24)
      });
      if (this.search.trim() !== "") {
        params.set("search", this.search.trim());
      }
      if (this.filterType !== "") {
        params.set("category", this.filterType);
      }
      try {
        const resp = await fetch(`${dataUrl}?${params.toString()}`, {
          credentials: "include",
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest"
          }
        });
        if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}`);
        }
        const payload = await resp.json();
        const apiWrapper = isObject(payload?.data?.data) ? payload.data.data : isObject(payload?.data) ? payload.data : {};
        const files = Array.isArray(apiWrapper?.data) ? apiWrapper.data : [];
        const meta = isObject(apiWrapper?.meta) ? apiWrapper.meta : {};
        this.files = files;
        this.pagination = {
          current_page: Number(meta.current_page ?? page),
          last_page: Math.max(1, Number(meta.last_page ?? 1)),
          total_items: Number(meta.total_items ?? meta.total ?? files.length),
          per_page: Number(meta.per_page ?? meta.limit ?? 24)
        };
      } catch (err) {
        devError("[filePicker] loadFiles error:", err);
        this.error = true;
        this.errorMessage = (uiLabels[localePrefix()] || uiLabels.es).loadRetry;
        this.files = [];
      } finally {
        this.loading = false;
      }
    },
    isSelected(file) {
      return this.selected.some((f) => String(f.id) === String(file.id));
    },
    toggleSelected(file) {
      if (this.isSelected(file)) {
        this.selected = this.selected.filter((f) => String(f.id) !== String(file.id));
      } else {
        this.selected.push(file);
      }
    },
    select(file) {
      if (this.multiSelect) {
        this.toggleSelected(file);
      } else {
        if (typeof this._onSelect === "function") {
          this._onSelect(file);
        }
        this.close();
      }
    },
    confirm() {
      if (typeof this._onSelectMulti === "function") {
        this._onSelectMulti([...this.selected]);
      }
      this.close();
    },
    onUploadFileChange(event) {
      const file = event?.target?.files?.[0] ?? null;
      if (file) {
        this._uploadFile = file;
        this.uploadFileName = file.name;
        this.uploadError = "";
      } else {
        this._uploadFile = null;
        this.uploadFileName = "";
      }
    },
    async submitUpload() {
      if (!this._uploadFile || this.uploading) {
        return;
      }
      this.uploading = true;
      this.uploadProgress = 0;
      this.uploadError = "";
      const panel = this._panel();
      const uploadUrl = String(panel?.dataset?.uploadUrl || "/files/upload");
      const csrfName = String(panel?.dataset?.csrfName || "");
      const csrfHash = String(panel?.dataset?.csrfHash || "");
      const formData = new FormData();
      formData.append("file", this._uploadFile);
      if (csrfName !== "" && csrfHash !== "") {
        formData.append(csrfName, csrfHash);
      }
      try {
        await new Promise((resolve, reject) => {
          const xhr = new XMLHttpRequest();
          xhr.upload.addEventListener("progress", (e) => {
            if (e.lengthComputable) {
              this.uploadProgress = Math.round(e.loaded / e.total * 90);
            }
          });
          xhr.open("POST", uploadUrl);
          xhr.setRequestHeader("Accept", "application/json");
          xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
          xhr.onload = () => {
            let json = null;
            try {
              json = JSON.parse(xhr.responseText);
            } catch {
            }
            if (json?.csrf_name && json?.csrf_hash && panel) {
              panel.dataset.csrfName = String(json.csrf_name);
              panel.dataset.csrfHash = String(json.csrf_hash);
            }
            if (xhr.status >= 200 && xhr.status < 300 && json?.ok !== false) {
              resolve(json);
            } else {
              const msg = json?.messages?.[0] || json?.message || `HTTP ${xhr.status}`;
              reject(new Error(String(msg)));
            }
          };
          xhr.onerror = () => reject(new Error("Network error"));
          xhr.send(formData);
        });
        this.uploadProgress = 100;
        this._uploadFile = null;
        this.uploadFileName = "";
        this.switchTab("library");
        this.loadFiles(1);
      } catch (err) {
        devError("[filePicker] submitUpload error:", err);
        this.uploadError = err instanceof Error ? err.message : "Upload failed.";
      } finally {
        this.uploading = false;
        this.uploadProgress = 0;
      }
    }
  };

  // src/js/components/appShell.js
  var appShell = () => ({
    sidebarOpen: window.innerWidth >= 768
  });

  // src/js/utils/badges.js
  var statusBadgeClass = (status) => {
    const val = String(status || "").toLowerCase();
    if (["active", "approved", "success"].includes(val)) {
      return "bg-green-100 text-green-800";
    }
    if (["pending", "pending_approval", "processing"].includes(val)) {
      return "bg-yellow-100 text-yellow-800";
    }
    if (["suspended", "rejected", "failed"].includes(val)) {
      return "bg-red-100 text-red-800";
    }
    return "bg-gray-100 text-gray-800";
  };
  var auditActionBadgeClass = (action) => {
    const val = String(action || "").toLowerCase();
    if (val === "create") return "bg-green-100 text-green-800";
    if (val === "update") return "bg-blue-100 text-blue-800";
    if (val === "delete") return "bg-red-100 text-red-800";
    if (["login", "login_success"].includes(val)) return "bg-brand-100 text-brand-800";
    if (val === "login_failure") return "bg-red-100 text-red-800";
    if (val === "logout") return "bg-gray-100 text-gray-800";
    if (val === "approve") return "bg-emerald-100 text-emerald-800";
    return "bg-gray-100 text-gray-700";
  };
  var auditResultBadgeClass = (result) => {
    const val = String(result || "").toLowerCase();
    if (val === "success") return "bg-green-100 text-green-800";
    if (val === "failure") return "bg-red-100 text-red-800";
    if (val === "denied") return "bg-orange-100 text-orange-800";
    return "bg-gray-100 text-gray-700";
  };
  var auditSeverityBadgeClass = (severity) => {
    const val = String(severity || "").toLowerCase();
    if (val === "info") return "bg-blue-50 text-blue-700 border border-blue-200";
    if (val === "warning") return "bg-amber-50 text-amber-700 border border-amber-200";
    if (val === "critical") return "bg-red-100 text-red-700 border border-red-300 font-bold";
    return "bg-gray-100 text-gray-600 border border-gray-200";
  };

  // src/js/utils/date.js
  var toDateInput = (value) => {
    if (value === null || value === void 0) {
      return null;
    }
    if (typeof value === "string" || typeof value === "number") {
      return value;
    }
    if (Array.isArray(value)) {
      return value.length > 0 ? toDateInput(value[0]) : null;
    }
    if (typeof value === "object") {
      if (typeof value.date === "string" || typeof value.date === "number") {
        return value.date;
      }
      if (typeof value.datetime === "string" || typeof value.datetime === "number") {
        return value.datetime;
      }
      if (typeof value.created_at === "string" || typeof value.created_at === "number") {
        return value.created_at;
      }
      if (typeof value.value === "string" || typeof value.value === "number") {
        return value.value;
      }
    }
    return null;
  };
  var formatDate = (value) => {
    const candidate = toDateInput(value);
    if (candidate === null || candidate === "") {
      return "-";
    }
    const date = new Date(candidate);
    if (Number.isNaN(date.getTime())) {
      return String(candidate);
    }
    return new Intl.DateTimeFormat(localeTag(), {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit"
    }).format(date);
  };

  // src/js/components/remoteTable.js
  var remoteTableFactory = (config = {}) => {
    const text = uiLabels[localePrefix()] || uiLabels.es;
    return {
      apiUrl: config.apiUrl || window.location.pathname,
      pageUrl: config.pageUrl || window.location.pathname,
      mode: config.mode || "generic",
      routes: config.routes || {},
      csrf: config.csrf || { name: "", hash: "" },
      defaultSort: typeof config.defaultSort === "string" ? config.defaultSort : "",
      limitOptions: Array.isArray(config.limitOptions) && config.limitOptions.length > 0 ? config.limitOptions : ["10", "25", "50", "100"],
      confirmDelete: config.confirmDelete || text.confirm,
      loading: false,
      error: false,
      errorMessage: "",
      rows: [],
      summary: {},
      pagination: {
        mode: "page",
        current_page: 1,
        last_page: 1,
        total_items: 0,
        limit: 25,
        from: 0,
        to: 0,
        next_cursor: "",
        prev_cursor: ""
      },
      page_input: "1",
      query: {},
      filterDefaults: {},
      filterFields: /* @__PURE__ */ new Set(),
      ignoredFilterKeys: /* @__PURE__ */ new Set(["sort", "page", "cursor", "order_by", "order_dir", "per_page", "limit"]),
      requestId: 0,
      debounceTimers: /* @__PURE__ */ new WeakMap(),
      form: null,
      init() {
        this.form = this.$el.querySelector('form[data-table-filter-form="1"]');
        this.loadFilterConfig();
        const fromUrl = queryToObject(window.location.search);
        this.query = { ...this.defaultFilterQuery(), ...fromUrl };
        if (typeof this.query.sort !== "string" || this.query.sort.trim() === "") {
          const defaultSort = String(this.defaultSort || "").trim();
          if (defaultSort !== "") {
            this.query.sort = defaultSort;
          }
        }
        this.applyQueryToForm();
        this.bindFormEvents();
        this.fetchData(false);
        window.addEventListener("popstate", () => {
          this.query = { ...this.defaultFilterQuery(), ...queryToObject(window.location.search) };
          this.applyQueryToForm();
          this.fetchData(false);
        });
      },
      loadFilterConfig() {
        this.filterDefaults = {};
        this.filterFields = /* @__PURE__ */ new Set();
        this.ignoredFilterKeys = /* @__PURE__ */ new Set(["sort", "page", "cursor"]);
        if (!this.form) {
          return;
        }
        const fieldElements = this.form.querySelectorAll("input[name], select[name], textarea[name]");
        fieldElements.forEach((el) => {
          const name = el.getAttribute("name");
          if (!name) {
            return;
          }
          this.filterFields.add(name);
          if (this.filterDefaults[name] === void 0) {
            this.filterDefaults[name] = "";
          }
        });
        const defaultsRaw = String(this.form.dataset.filterDefaults || "").trim();
        if (defaultsRaw !== "") {
          try {
            const parsed = JSON.parse(defaultsRaw);
            if (isObject(parsed)) {
              Object.entries(parsed).forEach(([key, value]) => {
                if (typeof key !== "string" || key.trim() === "") {
                  return;
                }
                this.filterFields.add(key);
                this.filterDefaults[key] = String(value ?? "").trim();
              });
            }
          } catch {
            return;
          }
        }
        const ignoredRaw = String(this.form.dataset.filterIgnored || "").trim();
        if (ignoredRaw !== "") {
          try {
            const parsed = JSON.parse(ignoredRaw);
            if (Array.isArray(parsed)) {
              parsed.forEach((key) => {
                if (typeof key === "string" && key.trim() !== "") {
                  this.ignoredFilterKeys.add(key);
                }
              });
            }
          } catch {
            return;
          }
        }
      },
      hasActiveFilters() {
        if (!this.form || this.form.dataset.reactiveHasFilters !== "1") {
          return false;
        }
        const keys = /* @__PURE__ */ new Set([
          ...Array.from(this.filterFields),
          ...Object.keys(this.query || {})
        ]);
        for (const key of keys) {
          if (this.ignoredFilterKeys.has(key)) {
            continue;
          }
          if (!this.filterFields.has(key) && this.filterDefaults[key] === void 0) {
            continue;
          }
          const defaultValue = String(this.filterDefaults[key] ?? "").trim();
          const currentValue = Object.prototype.hasOwnProperty.call(this.query, key) ? String(this.query[key] ?? "").trim() : "";
          if (currentValue !== defaultValue) {
            return true;
          }
        }
        return false;
      },
      defaultFilterQuery() {
        const query = {};
        Object.entries(this.filterDefaults || {}).forEach(([key, value]) => {
          const normalized = String(value ?? "").trim();
          if (normalized !== "") {
            query[key] = normalized;
          }
        });
        return query;
      },
      bindFormEvents() {
        if (!this.form) {
          return;
        }
        this.form.addEventListener("submit", (event) => {
          event.preventDefault();
          const activeSort = typeof this.query.sort === "string" ? this.query.sort : "";
          this.query = formToQuery(this.form);
          if (activeSort !== "") {
            this.query.sort = activeSort;
          }
          this.query.page = "";
          this.query.cursor = "";
          this.fetchData(true);
        });
        this.form.querySelectorAll("[data-table-debounce]").forEach((input) => {
          input.addEventListener("input", () => {
            const previousTimer = this.debounceTimers.get(input);
            if (previousTimer) {
              clearTimeout(previousTimer);
            }
            const wait = Number.parseInt(input.dataset.tableDebounce || "350", 10);
            const timer = setTimeout(() => {
              const activeSort = typeof this.query.sort === "string" ? this.query.sort : "";
              this.query = formToQuery(this.form);
              if (activeSort !== "") {
                this.query.sort = activeSort;
              }
              this.query.page = "";
              this.query.cursor = "";
              this.fetchData(true);
            }, Number.isFinite(wait) ? wait : 350);
            this.debounceTimers.set(input, timer);
          });
        });
      },
      applyQueryToForm() {
        if (!this.form) {
          return;
        }
        const elements = this.form.querySelectorAll("input[name], select[name], textarea[name]");
        elements.forEach((el) => {
          const name = el.getAttribute("name");
          if (!name) {
            return;
          }
          const value = this.query[name] ?? "";
          if (el.type === "checkbox" || el.type === "radio") {
            el.checked = String(el.value) === value;
          } else {
            el.value = value;
          }
        });
      },
      buildUrl(base, query) {
        const url = new URL(base, window.location.origin);
        const qs = objectToQueryString(query);
        url.search = qs;
        return url.toString();
      },
      async fetchData(pushHistory = true) {
        this.loading = true;
        this.error = false;
        this.errorMessage = "";
        this.requestId += 1;
        const requestId = this.requestId;
        const apiUrl = this.buildUrl(this.apiUrl, this.query);
        const pageUrl = this.buildUrl(this.pageUrl, this.query);
        const fetchLocale = localePrefix();
        const pageText = uiLabels[fetchLocale] || uiLabels.es;
        try {
          const response = await fetch(apiUrl, {
            credentials: "include",
            headers: {
              Accept: "application/json",
              "X-Requested-With": "XMLHttpRequest"
            }
          });
          const rawBody = await response.text();
          let payload = {};
          if (rawBody.trim() !== "") {
            try {
              payload = JSON.parse(rawBody);
            } catch (e) {
              devError("JSON Parse error in fetchData:", e);
              if (requestId === this.requestId) {
                this.rows = [];
                this.error = true;
                this.errorMessage = pageText.loadRetry;
              }
              return;
            }
          }
          if (requestId !== this.requestId) {
            return;
          }
          if (!response.ok) {
            this.rows = [];
            this.summary = {};
            this.pagination = {
              mode: "page",
              current_page: 1,
              last_page: 1,
              total_items: 0,
              limit: 25,
              from: 0,
              to: 0,
              next_cursor: "",
              prev_cursor: ""
            };
            this.page_input = "1";
            this.error = true;
            this.errorMessage = this.resolveErrorMessage(payload, response.status);
            return;
          }
          const root = tablePayloadRoot(payload);
          this.rows = this.extractRows(root);
          this.summary = this.extractSummary(root);
          this.pagination = this.extractPagination(root, this.rows.length);
          this.page_input = String(this.pagination.current_page);
          this.$nextTick(() => {
            bootLucideIcons();
          });
          if (pushHistory) {
            window.history.pushState({}, "", pageUrl);
          }
        } catch (err) {
          devError("Fetch error in fetchData:", err);
          if (requestId !== this.requestId) {
            return;
          }
          this.rows = [];
          this.summary = {};
          this.error = true;
          this.errorMessage = pageText.loadRetry;
          this.page_input = "1";
        } finally {
          if (requestId === this.requestId) {
            this.loading = false;
          }
        }
      },
      extractRows(root) {
        if (Array.isArray(root.data)) {
          return root.data;
        }
        if (isObject(root.data) && Array.isArray(root.data.data)) {
          return root.data.data;
        }
        if (Array.isArray(root.items)) {
          return root.items;
        }
        const commonKeys = ["users", "files", "audit", "api_keys", "keys", "logs", "entries"];
        for (const key of commonKeys) {
          if (Array.isArray(root[key])) {
            return root[key];
          }
          if (isObject(root.data) && Array.isArray(root.data[key])) {
            return root.data[key];
          }
        }
        return [];
      },
      extractSummary(root) {
        if (isObject(root.summary)) {
          return root.summary;
        }
        if (isObject(root.data) && isObject(root.data.summary)) {
          return root.data.summary;
        }
        return {};
      },
      extractPagination(root, visibleCount) {
        const meta = isObject(root.meta) ? root.meta : {};
        const next_cursor = String(meta.next_cursor ?? root.next_cursor ?? "");
        const prev_cursor = String(meta.prev_cursor ?? root.prev_cursor ?? "");
        const hasCursor = next_cursor !== "" || prev_cursor !== "" || String(this.query.cursor || "") !== "";
        const limit = Number(meta.per_page ?? meta.limit ?? root.per_page ?? root.limit ?? this.query.limit ?? this.query.per_page ?? 25) || 25;
        const safeLimit = Math.max(1, limit);
        const total = Number(meta.total_items ?? meta.total ?? root.total_items ?? root.total ?? visibleCount) || visibleCount;
        const current_page = Number(meta.current_page ?? meta.page ?? root.current_page ?? root.page ?? this.query.page ?? 1) || 1;
        const derivedLastPage = Math.max(1, Math.ceil(Math.max(0, total) / safeLimit));
        const last_page = Number(meta.last_page ?? root.last_page ?? derivedLastPage) || derivedLastPage;
        const normalizedCurrentPage = Math.max(1, Math.min(current_page, Math.max(1, last_page)));
        const from = total <= 0 ? 0 : (normalizedCurrentPage - 1) * safeLimit + 1;
        let to = 0;
        if (total > 0) {
          if (visibleCount > 0) {
            to = Math.min(total, from + visibleCount - 1);
          } else {
            to = Math.min(total, normalizedCurrentPage * safeLimit);
          }
        }
        return {
          mode: hasCursor ? "cursor" : "page",
          current_page: normalizedCurrentPage,
          last_page: Math.max(1, last_page),
          total_items: Math.max(0, total),
          limit: safeLimit,
          from: Math.max(0, from),
          to: Math.max(0, to),
          next_cursor,
          prev_cursor
        };
      },
      resolveErrorMessage(payload, status) {
        if (isObject(payload)) {
          if (typeof payload.message === "string" && payload.message.trim() !== "") {
            return payload.message;
          }
          if (Array.isArray(payload.messages) && payload.messages.length > 0) {
            return String(payload.messages[0]);
          }
        }
        return text.requestFailed.replace("{status}", String(status));
      },
      isCursorMode() {
        return this.pagination.mode === "cursor";
      },
      hasPagination() {
        if (this.isCursorMode()) {
          return this.pagination.prev_cursor !== "" || this.pagination.next_cursor !== "";
        }
        return this.pagination.last_page > 1;
      },
      pageWindow() {
        const start = Math.max(1, this.pagination.current_page - 2);
        const end = Math.min(this.pagination.last_page, this.pagination.current_page + 2);
        const pages = [];
        for (let page = start; page <= end; page += 1) {
          pages.push(page);
        }
        return pages;
      },
      paginationLabel() {
        const currentLocale = localePrefix();
        const labels = paginationLabels[currentLocale] || paginationLabels.es;
        if (this.isCursorMode()) {
          return `${labels.visibleResults}: ${this.pagination.total_items}`;
        }
        if (this.pagination.total_items <= 0 || this.pagination.from <= 0) {
          return `${labels.showing} 0 ${labels.of} ${this.pagination.total_items}`;
        }
        return `${labels.showing} ${this.pagination.from}-${this.pagination.to} ${labels.of} ${this.pagination.total_items}`;
      },
      paginationLimitOptions() {
        const options = [];
        this.limitOptions.forEach((value) => {
          const parsed = Number.parseInt(String(value ?? ""), 10);
          if (!Number.isFinite(parsed) || parsed <= 0) {
            return;
          }
          options.push(parsed);
        });
        if (options.length === 0) {
          return [10, 25, 50, 100];
        }
        return Array.from(new Set(options)).sort((a, b) => a - b);
      },
      currentSort(field) {
        const sort = String(this.query.sort || "");
        if (sort === field) {
          return "asc";
        }
        if (sort === `-${field}`) {
          return "desc";
        }
        return "";
      },
      sortAria(field) {
        const direction = this.currentSort(field);
        if (direction === "asc") {
          return "ascending";
        }
        if (direction === "desc") {
          return "descending";
        }
        return "none";
      },
      sortIcon(field) {
        const direction = this.currentSort(field);
        if (direction === "asc") {
          return "\u2191";
        }
        if (direction === "desc") {
          return "\u2193";
        }
        return "\u2195";
      },
      toggleSort(field) {
        const current = this.currentSort(field);
        if (current === "asc") {
          this.query.sort = `-${field}`;
        } else if (current === "desc") {
          this.query.sort = "";
        } else {
          this.query.sort = field;
        }
        this.query.page = "";
        this.query.cursor = "";
        this.fetchData(true);
      },
      goToPage(page) {
        const boundedPage = Math.max(1, Math.min(this.pagination.last_page || 1, page));
        this.query.page = String(boundedPage);
        this.query.cursor = "";
        this.page_input = String(boundedPage);
        this.fetchData(true);
      },
      goToFirstPage() {
        if (this.isCursorMode() || this.pagination.current_page <= 1) {
          return;
        }
        this.goToPage(1);
      },
      goToLastPage() {
        if (this.isCursorMode() || this.pagination.current_page >= this.pagination.last_page) {
          return;
        }
        this.goToPage(this.pagination.last_page);
      },
      goToPageFromInput() {
        if (this.isCursorMode()) {
          return;
        }
        const page = Number.parseInt(String(this.page_input || ""), 10);
        if (!Number.isFinite(page) || page <= 0) {
          this.page_input = String(this.pagination.current_page);
          return;
        }
        this.goToPage(page);
      },
      goToCursor(cursor) {
        if (!cursor) {
          return;
        }
        this.query.cursor = String(cursor);
        this.query.page = "";
        this.fetchData(true);
      },
      onLimitChange(limit) {
        const parsed = Number.parseInt(String(limit || ""), 10);
        if (!Number.isFinite(parsed) || parsed <= 0) {
          this.query.limit = "";
        } else {
          const maxOption = Math.max(...this.paginationLimitOptions());
          this.query.limit = String(Math.min(maxOption, Math.max(1, parsed)));
        }
        this.query.page = "";
        this.query.cursor = "";
        this.page_input = "1";
        this.fetchData(true);
      },
      fullName(row) {
        const first_name = String(row.first_name ?? "").trim();
        const last_name = String(row.last_name ?? "").trim();
        const fullName = `${first_name} ${last_name}`.trim();
        return fullName === "" ? "-" : fullName;
      },
      statusBadgeClass,
      statusLabel,
      auditActionBadgeClass,
      auditActionLabel,
      auditResultBadgeClass,
      auditResultLabel,
      auditSeverityBadgeClass,
      auditSeverityLabel,
      formatDate,
      showUrl(id) {
        return `${this.routes.showBase}/${encodeURIComponent(String(id ?? ""))}`;
      },
      editUrl(id) {
        return `${this.routes.editBase}/${encodeURIComponent(String(id ?? ""))}/edit`;
      },
      userShowUrl(id) {
        return `${this.routes.showBase}/${encodeURIComponent(String(id ?? ""))}`;
      },
      userEditUrl(id) {
        return `${this.routes.editBase}/${encodeURIComponent(String(id ?? ""))}/edit`;
      },
      auditShowUrl(id) {
        return `${this.routes.showBase}/${encodeURIComponent(String(id ?? ""))}`;
      },
      fileDownloadUrl(id) {
        return `${this.routes.downloadBase}/${encodeURIComponent(String(id ?? ""))}/download`;
      },
      fileDeleteUrl(id) {
        return `${this.routes.deleteBase}/${encodeURIComponent(String(id ?? ""))}/delete`;
      },
      apiKeyShowUrl(id) {
        return `${this.routes.showBase}/${encodeURIComponent(String(id ?? ""))}`;
      },
      apiKeyEditUrl(id) {
        return `${this.routes.editBase}/${encodeURIComponent(String(id ?? ""))}/edit`;
      }
    };
  };

  // src/js/components/filePickerField.js
  var filePickerField = (config = {}) => ({
    fieldName: String(config.name || "file_id"),
    fileId: String(config.value || ""),
    fileInfo: {
      original_name: "",
      mime_type: "",
      category: "",
      is_image: false,
      url: "",
      human_size: ""
    },
    loading: false,
    _accept: String(config.accept || ""),
    _filterType: String(config.filterType || ""),
    init() {
      if (this.fileId !== "") {
        this._loadFileInfo(this.fileId);
      }
    },
    async _loadFileInfo(id) {
      if (!id) {
        return;
      }
      this.loading = true;
      const panel = document.getElementById("file-picker-panel");
      const baseUrl = panel?.dataset?.dataUrl ? String(panel.dataset.dataUrl).replace("/picker-data", "") : "/files";
      try {
        const resp = await fetch(`${baseUrl}/${encodeURIComponent(String(id))}/picker-info`, {
          credentials: "include",
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest"
          }
        });
        if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}`);
        }
        const payload = await resp.json();
        if (payload?.ok && isObject(payload?.data)) {
          const d = payload.data;
          this.fileInfo = {
            original_name: String(d.original_name || ""),
            mime_type: String(d.mime_type || ""),
            category: String(d.category || ""),
            is_image: Boolean(d.is_image),
            url: String(d.url || ""),
            human_size: String(d.human_size || "")
          };
        }
      } catch (err) {
        devError("[filePickerField] _loadFileInfo error:", err);
      } finally {
        this.loading = false;
      }
    },
    openPicker() {
      Alpine.store("filePicker").show({
        accept: this._accept,
        filterType: this._filterType,
        multi: false,
        onSelect: (file) => {
          this.fileId = String(file.id ?? "");
          this.fileInfo = {
            original_name: String(file.original_name || ""),
            mime_type: String(file.mime_type || ""),
            category: String(file.category || ""),
            is_image: Boolean(file.is_image),
            url: String(file.url || ""),
            human_size: String(file.human_size || "")
          };
        }
      });
    }
  });

  // src/js/components/adminMetadataField.js
  var adminMetadataField = (config = {}) => ({
    rows: Array.isArray(config.rows) && config.rows.length > 0 ? config.rows : [{ key: "", value: "" }],
    json: "{}",
    duplicates: [],
    messages: {
      pastePrompt: config.jsonPastePrompt || "Paste JSON object here:",
      invalidFormat: config.jsonInvalidFormat || "Invalid JSON: Must be an object.",
      syntaxError: config.jsonSyntaxError || "Invalid JSON syntax: {error}"
    },
    init() {
      this.sync();
    },
    addRow() {
      this.rows.push({ key: "", value: "" });
      this.sync();
    },
    removeRow(index) {
      this.rows.splice(index, 1);
      if (this.rows.length === 0) {
        this.addRow();
        return;
      }
      this.sync();
    },
    importJson() {
      const raw = prompt(this.messages.pastePrompt);
      if (!raw) return;
      try {
        const parsed = JSON.parse(raw);
        if (typeof parsed !== "object" || parsed === null || Array.isArray(parsed)) {
          alert(this.messages.invalidFormat);
          return;
        }
        const newRows = Object.entries(parsed).map(([key, value]) => ({
          key,
          value: typeof value === "object" ? JSON.stringify(value) : String(value)
        }));
        if (newRows.length > 0) {
          this.rows = newRows;
          this.sync();
        }
      } catch (e) {
        alert(this.messages.syntaxError.replace("{error}", e.message));
      }
    },
    sync() {
      const metadata = {};
      const keys = [];
      this.duplicates = [];
      this.rows.forEach((row, index) => {
        const key = String(row.key || "").trim();
        if (key === "") return;
        if (keys.includes(key)) {
          this.duplicates.push(index);
        }
        keys.push(key);
        let val = String(row.value || "").trim();
        if (val === "true") val = true;
        else if (val === "false") val = false;
        else if (val === "null") val = null;
        else if (!isNaN(val) && val !== "") val = Number(val);
        metadata[key] = val;
      });
      this.json = JSON.stringify(metadata, null, 2);
    }
  });

  // src/js/components/adminMediaGallery.js
  var normalizePickerFile = (file) => {
    if (!file) return {};
    return {
      id: file.id ?? "",
      original_name: file.original_name ?? file.name ?? "",
      mime_type: file.mime_type ?? "",
      category: file.category ?? "",
      is_image: file.is_image ?? file.category === "image",
      url: file.url ?? "",
      human_size: file.human_size ?? "",
      variants: file.variants ?? {}
    };
  };
  var adminMediaGallery = (config = {}) => ({
    rows: Array.isArray(config.rows) ? config.rows : [],
    init() {
      if (this.rows.length === 0) {
        this.addRow("cover");
      }
      this.rows.forEach((row) => {
        if (!isObject(row.file)) {
          row.file = {};
        }
        if (row.hub_file_id) {
          this.loadFileInfo(row);
        }
      });
    },
    addRow(type = "gallery") {
      this.rows.push({
        type,
        hub_file_id: "",
        external_url: "",
        alt_text: "",
        caption: "",
        sort_order: this.rows.length,
        is_active: true,
        file: {}
      });
    },
    removeRow(index) {
      this.rows.splice(index, 1);
    },
    chooseFile(row) {
      Alpine.store("filePicker").show({
        filterType: "image",
        accept: "image/*",
        multi: false,
        onSelect: (file) => {
          const selected = normalizePickerFile(file);
          row.hub_file_id = String(selected.id ?? "");
          row.external_url = "";
          row.file = {
            original_name: String(selected.original_name || ""),
            mime_type: String(selected.mime_type || ""),
            category: String(selected.category || ""),
            is_image: Boolean(selected.is_image),
            url: String(selected.url || ""),
            human_size: String(selected.human_size || ""),
            variants: selected.variants || {}
          };
        }
      });
    },
    clearFile(row) {
      row.hub_file_id = "";
      row.file = {};
    },
    fileName(row) {
      return String(row.file?.original_name || (row.hub_file_id ? `#${row.hub_file_id}` : ""));
    },
    async loadFileInfo(row) {
      const panel = document.getElementById("file-picker-panel");
      const baseUrl = panel?.dataset?.dataUrl ? String(panel.dataset.dataUrl).replace("/picker-data", "") : "/files";
      try {
        const resp = await fetch(`${baseUrl}/${encodeURIComponent(String(row.hub_file_id))}/picker-info`, {
          credentials: "include",
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest"
          }
        });
        if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}`);
        }
        const payload = await resp.json();
        if (payload?.ok && isObject(payload?.data)) {
          const d = normalizePickerFile(payload.data);
          row.file = {
            original_name: String(d.original_name || ""),
            mime_type: String(d.mime_type || ""),
            category: String(d.category || ""),
            is_image: Boolean(d.is_image),
            url: String(d.url || ""),
            human_size: String(d.human_size || ""),
            variants: d.variants || {}
          };
        }
      } catch (err) {
        devError("[adminMediaGallery] loadFileInfo error:", err);
      }
    }
  });

  // src/js/components/sessionWatcher.js
  var bootSessionExpiryWatcher = (config = {}) => {
    const meta = document.querySelector('meta[name="session-expires-at"]');
    if (!(meta instanceof HTMLMetaElement)) {
      return;
    }
    const expiresAt = parseInt(meta.getAttribute("content") || "0", 10);
    if (!Number.isFinite(expiresAt) || expiresAt <= 0) {
      return;
    }
    const WARN_BEFORE_SECONDS = 60;
    let warned = false;
    const expiringMessage = config.expiringMessage || "Token expires in ~{seconds}s. Save your work.";
    const tick = () => {
      const remaining = expiresAt - Math.floor(Date.now() / 1e3);
      if (!warned && remaining > 0 && remaining <= WARN_BEFORE_SECONDS) {
        warned = true;
        const message = `[session] ${expiringMessage.replace("{seconds}", remaining)}`;
        console.warn(message);
        window.dispatchEvent(new CustomEvent("session:expiring-soon", {
          detail: { remainingSeconds: remaining, message }
        }));
      }
      if (remaining <= 0) {
        window.dispatchEvent(new CustomEvent("session:expired"));
        clearInterval(handle);
      }
    };
    const handle = setInterval(tick, 5e3);
    tick();
  };

  // src/js/components/googleAuth.js
  var handleGoogleCredentialResponse = (response) => {
    const token = response && typeof response.credential === "string" ? response.credential : "";
    if (token === "") {
      devError("[Google Auth] Empty credential in response");
      return;
    }
    const tokenInput = document.getElementById("google-id-token");
    const loginForm = document.getElementById("google-login-form");
    if (!(tokenInput instanceof HTMLInputElement) || !(loginForm instanceof HTMLFormElement)) {
      devError("[Google Auth] Required form elements not found in DOM");
      return;
    }
    window.dispatchEvent(new CustomEvent("login:loading", { detail: { flow: "google" } }));
    tokenInput.value = token;
    loginForm.submit();
  };

  // src/js/app.js
  document.addEventListener("alpine:init", () => {
    Alpine.store("confirm", confirmStore());
    Alpine.store("toast", toastStore);
    Alpine.store("filePicker", filePickerStore);
    Alpine.data("appShell", appShell);
    Alpine.data("remoteTable", remoteTableFactory);
    Alpine.data("filePickerField", filePickerField);
    Alpine.data("adminMetadataField", adminMetadataField);
    Alpine.data("adminMediaGallery", adminMediaGallery);
    Alpine.data("catalogMetadataField", (config = {}) => Alpine.data("adminMetadataField")(config));
    Alpine.data("catalogItemMedia", (config = {}) => Alpine.data("adminMediaGallery")(config));
    window.remoteTable = remoteTableFactory;
  });
  document.addEventListener("DOMContentLoaded", () => {
    bootLucideIcons();
    bootSlugFields();
    bootGlobalSubmitGuard();
    const config = window.__componentConfig || {};
    bootSessionExpiryWatcher({ expiringMessage: config.sessionExpiringMessage });
  });
  window.addEventListener("load", () => {
    bootLucideIcons();
  });
  window.handleGoogleCredentialResponse = handleGoogleCredentialResponse;
})();
