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
    el.setAttribute('disabled', '');
    const url = new URL(`${el.dataset.url}index.php?option=com_ajax&format=json&module=publicfolder&method=create&folder=${input.value}`);
    if (!url) return;

    try {
      resp = await fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token') || '' } });
    } catch (err) {
      renderMessage('error', ['Internal error']);
    }

    if (resp.status !== 200) {
      renderMessage('error', ['Internal error']);
      return;
    }
    const respJson = await resp.json();
    if (respJson.error) {
      renderMessage('error', [respJson.message]);
    } else {
      renderMessage('success', [respJson.message]);
    }
    el.removeAttribute('disabled');
    input.form.classList.add('visually-hidden');
    input.form.nextElementSibling.classList.remove('visually-hidden');
    input.form.nextElementSibling.innerHTML = input.form.nextElementSibling.innerHTML.replace('{{path}}', input.value);
  });
  button.removeAttribute('disabled');
});
