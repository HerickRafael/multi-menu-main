(function () {
  const cpfInput = document.getElementById('cpf-input');

  function formatCPF(value) {
    const numbers = value.replace(/\D/g, '');

    if (numbers.length <= 3) {
      return numbers;
    }

    if (numbers.length <= 6) {
      return numbers.slice(0, 3) + '.' + numbers.slice(3);
    }

    if (numbers.length <= 9) {
      return numbers.slice(0, 3) + '.' + numbers.slice(3, 6) + '.' + numbers.slice(6);
    }

    return numbers.slice(0, 3) + '.' + numbers.slice(3, 6) + '.' + numbers.slice(6, 9) + '-' + numbers.slice(9, 11);
  }

  if (cpfInput) {
    cpfInput.addEventListener('input', function (e) {
      const cursorPos = e.target.selectionStart;
      const oldLength = e.target.value.length;

      e.target.value = formatCPF(e.target.value);

      const newLength = e.target.value.length;
      const diff = newLength - oldLength;
      e.target.setSelectionRange(cursorPos + diff, cursorPos + diff);
    });

    if (cpfInput.value) {
      cpfInput.value = formatCPF(cpfInput.value);
    }
  }
})();

(function () {
  const birthdateInput = document.getElementById('birthdate-input');
  const errorEl = document.getElementById('birthdate-error');
  const form = birthdateInput ? birthdateInput.closest('form') : null;

  if (!birthdateInput || !errorEl) {
    return;
  }

  function validateBirthdate() {
    const value = birthdateInput.value;
    errorEl.style.display = 'none';
    errorEl.textContent = '';
    birthdateInput.style.borderColor = '';

    if (!value) {
      return true;
    }

    const birthDate = new Date(value + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (birthDate > today) {
      errorEl.textContent = 'Data não pode ser no futuro';
      errorEl.style.display = 'block';
      birthdateInput.style.borderColor = '#dc2626';
      return false;
    }

    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }

    if (age < 13) {
      errorEl.textContent = 'Você precisa ter pelo menos 13 anos';
      errorEl.style.display = 'block';
      birthdateInput.style.borderColor = '#dc2626';
      return false;
    }

    if (age > 120) {
      errorEl.textContent = 'Verifique o ano de nascimento';
      errorEl.style.display = 'block';
      birthdateInput.style.borderColor = '#dc2626';
      return false;
    }

    return true;
  }

  birthdateInput.addEventListener('change', validateBirthdate);
  birthdateInput.addEventListener('blur', validateBirthdate);

  if (form) {
    form.addEventListener('submit', function (e) {
      if (!validateBirthdate()) {
        e.preventDefault();
        birthdateInput.focus();
      }
    });
  }
})();

// Listener data-confirm consolidado em public/assets/shared.js
