const renderMessage = (type, msg) => {
  if (Joomla && Joomla.renderMessages && typeof Joomla.renderMessages === 'function') {
    Joomla.renderMessages({ [type]: msg });
  } else {
    alert(msg);
  }
};

[...document.querySelectorAll('.publicFolderForm')].forEach((form) => {
  const button = form.querySelector('button');
  const input = form.querySelector('input');
  button.addEventListener('click', async (event) => {
    let resp;
    const el = event.currentTarget;
    const url = new URL(`${el.dataset.url}index.php?option=com_ajax&format=json&module=publicfolder&method=create&folder=${input.value}`);
    if (!url) return;

    el.setAttribute('disabled', '');

    try {
      resp = await fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token') || '' } });
    } catch (err) {
      renderMessage('error', ['Internal error']);
      el.removeAttribute('disabled');
    }

    if (resp.status !== 200) {
      renderMessage('error', ['Internal error']);
      el.removeAttribute('disabled');
      return;
    }

    const respJson = await resp.json();
    if (!respJson) {
      renderMessage('error', ['Internal error']);
      el.removeAttribute('disabled');
      return;
    }
    renderMessage(respJson.error ? 'error' : 'success', [respJson.message]);
    el.removeAttribute('disabled');
    input.form.classList.add('visually-hidden');
    input.form.nextElementSibling.classList.remove('visually-hidden');
    input.form.nextElementSibling.innerHTML = input.form.nextElementSibling.innerHTML.replace('{{path}}', Joomla.sanitizeHtml(input.value));
  });
  button.removeAttribute('disabled');
});
