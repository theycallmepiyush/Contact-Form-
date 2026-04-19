'use strict';
 
// ── DOM References ───────────────────────────────────────────
const form         = document.getElementById('contactForm');
const submitBtn    = document.getElementById('submitBtn');
const successState = document.getElementById('successState');
const sendAgainBtn = document.getElementById('sendAnotherBtn');
const ticketNumEl  = document.getElementById('ticketNumber');
const charCountEl  = document.getElementById('messageCharCount');
 
const fields = {
  fname:    document.getElementById('fname'),
  lname:    document.getElementById('lname'),
  email:    document.getElementById('email'),
  phone:    document.getElementById('phone'),
  category: document.getElementById('category'),
  priority: document.getElementById('priority'),
  subject:  document.getElementById('subject'),
  message:  document.getElementById('message'),
  orderId:  document.getElementById('orderId'),
};
 
const errorSpans = {
  fname:    document.getElementById('fnameErr'),
  lname:    document.getElementById('lnameErr'),
  email:    document.getElementById('emailErr'),
  phone:    document.getElementById('phoneErr'),
  category: document.getElementById('categoryErr'),
  priority: document.getElementById('priorityErr'),
  subject:  document.getElementById('subjectErr'),
  message:  document.getElementById('messageErr'),
  orderId:  document.getElementById('orderIdErr'),
};
 
 
// ── Validation Rules ─────────────────────────────────────────
const rules = {
  fname(val) {
    if (!val)           return 'First name is required.';
    if (val.length < 2) return 'Must be at least 2 characters.';
    if (!/^[a-zA-Z\s\-']+$/.test(val)) return 'Please enter a valid name.';
    return '';
  },
  lname(val) {
    if (!val)           return 'Last name is required.';
    if (val.length < 2) return 'Must be at least 2 characters.';
    return '';
  },
  email(val) {
    if (!val) return 'Email address is required.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val))
      return 'Please enter a valid email address.';
    return '';
  },
  phone(val) {
    if (!val) return '';
    if (!/^[\d\s\+\-\(\)]{7,15}$/.test(val))
      return 'Please enter a valid phone number.';
    return '';
  },
  category(val) {
    if (!val) return 'Please select an issue category.';
    return '';
  },
  priority(val) {
    if (!val) return 'Please select a priority level.';
    return '';
  },
  subject(val) {
    if (!val)             return 'A subject is required.';
    if (val.length < 5)   return 'Subject must be at least 5 characters.';
    if (val.length > 120) return 'Subject must not exceed 120 characters.';
    return '';
  },
  message(val) {
    if (!val)             return 'A description of the issue is required.';
    if (val.length < 20)  return 'Please provide more detail (minimum 20 characters).';
    if (val.length > 800) return 'Description must not exceed 800 characters.';
    return '';
  },
  orderId() { return ''; },
};
 
 
// ── Field State Helpers ──────────────────────────────────────
function setFieldState(name, errorMsg) {
  const el       = fields[name];
  const errSpan  = errorSpans[name];
  const fieldDiv = document.getElementById('field-' + name);
  if (!el || !errSpan) return;
  errSpan.textContent = errorMsg;
  if (fieldDiv) {
    fieldDiv.classList.toggle('field--error', !!errorMsg);
    fieldDiv.classList.toggle('field--valid', !errorMsg && el.value.trim().length > 0);
  }
}
 
function validateField(name) {
  const val = (fields[name] ? fields[name].value : '').trim();
  const msg = rules[name] ? rules[name](val) : '';
  setFieldState(name, msg);
  return msg === '';
}
 
function validateAll() {
  return Object.keys(rules).map(validateField).every(Boolean);
}
 
 
// ── Character Counter ────────────────────────────────────────
if (fields.message) {
  fields.message.addEventListener('input', function () {
    const len = this.value.length;
    charCountEl.textContent = len + ' / 800';
    charCountEl.classList.toggle('char-count--warn',  len > 680 && len <= 760);
    charCountEl.classList.toggle('char-count--limit', len > 760);
  });
}
 
 
// ── Blur Validation ──────────────────────────────────────────
Object.keys(fields).forEach(function(name) {
  const el = fields[name];
  if (!el) return;
  const evt = (el.tagName === 'SELECT') ? 'change' : 'blur';
  el.addEventListener(evt, function() { validateField(name); });
  if (el.tagName !== 'SELECT') {
    el.addEventListener('input', function() {
      const fd = document.getElementById('field-' + name);
      if (fd && fd.classList.contains('field--error')) validateField(name);
    });
  }
});
 
 
// ── UI Helpers ───────────────────────────────────────────────
function setLoading(on) {
  submitBtn.disabled = on;
  submitBtn.classList.toggle('loading', on);
}
 
function showSuccess(ticketId) {
  var num = Math.floor(100000 + Math.random() * 900000);
  ticketNumEl.textContent = ticketId || ('SUP-' + num);
  form.hidden = true;
  successState.hidden = false;
  successState.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
 
function resetForm() {
  successState.hidden = true;
  form.hidden = false;
  form.reset();
  Object.keys(fields).forEach(function(name) {
    setFieldState(name, '');
    var fd = document.getElementById('field-' + name);
    if (fd) { fd.classList.remove('field--valid', 'field--error'); }
  });
  charCountEl.textContent = '0 / 800';
  charCountEl.className = 'char-count';
  setLoading(false);
}
 
function showBannerError(msg) {
  var banner = document.getElementById('serverErrBanner');
  if (!banner) {
    banner = document.createElement('div');
    banner.id = 'serverErrBanner';
    banner.style.cssText = 'background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;font-size:0.85rem;color:#dc2626;font-weight:500;margin-bottom:12px;';
    submitBtn.parentElement.insertBefore(banner, submitBtn);
  }
  banner.textContent = '⚠  ' + msg;
  setTimeout(function() { if (banner) banner.remove(); }, 8000);
}
 
 
// ── Form Submit ──────────────────────────────────────────────
form.addEventListener('submit', function(e) {
  e.preventDefault();
 
  if (!validateAll()) {
    var firstBad = Object.keys(fields).find(function(name) {
      var fd = document.getElementById('field-' + name);
      return fd && fd.classList.contains('field--error');
    });
    if (firstBad) fields[firstBad].focus();
    return;
  }
 
  setLoading(true);
 
  var formData = new FormData(form);
 
  // Use XMLHttpRequest instead of fetch — more reliable on localhost
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'submit.php', true);
 
  xhr.onload = function() {
    setLoading(false);
    console.log('Status:', xhr.status);
    console.log('Response:', xhr.responseText);
 
    if (xhr.status === 200 || xhr.status === 422) {
      try {
        var result = JSON.parse(xhr.responseText);
        if (result.success) {
          showSuccess(result.ticket);
        } else {
          showBannerError(result.message || 'An error occurred. Please try again.');
        }
      } catch (err) {
        console.error('Could not parse response:', xhr.responseText);
        showBannerError('Server returned an unexpected response. Check browser console (F12).');
      }
    } else {
      showBannerError('Server error ' + xhr.status + '. Check browser console (F12) for details.');
    }
  };
 
  xhr.onerror = function() {
    setLoading(false);
    console.error('XHR network error');
    showBannerError('Could not connect to server. Make sure XAMPP Apache is running.');
  };
 
  xhr.send(formData);
});
 
sendAgainBtn.addEventListener('click', resetForm);
 




