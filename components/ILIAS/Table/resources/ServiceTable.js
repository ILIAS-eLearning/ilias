// Hide all on load
const ilTableHideFilter = {};

/**
 * Hide all ilFormHelpLink elements
 */
function ilInitTableFilters() {
  // hide filters
  const filters = document.querySelectorAll('.ilTableFilterSec');
  filters.forEach((filter) => {
    filter.style.display = ilTableHideFilter[filter.id] ? 'none' : '';
  });

  // show filter activators
  const filterActivators = document.querySelectorAll('.ilTableFilterActivator');
  filterActivators.forEach((activator) => {
    activator.style.display = ilTableHideFilter[activator.id] ? '' : 'none';
  });

  // hide filter deactivators
  const filterDeactivators = document.querySelectorAll('.ilTableFilterDeactivator');
  filterDeactivators.forEach((deactivator) => {
    deactivator.style.display = ilTableHideFilter[deactivator.id] ? 'none' : '';
  });
}

function ilShowTableFilter(id, sUrl) {
  const filter = document.getElementById(id);
  const activator = document.getElementById(`a${id}`);
  const deactivator = document.getElementById(`d${id}`);

  if (filter && activator && deactivator) {
    filter.style.display = '';
    activator.style.display = 'none';
    deactivator.style.display = '';
  }

  if (sUrl) {
    ilTableJSHandler(sUrl);
  }

  return false;
}

function ilHideTableFilter(id, sUrl) {
  const filter = document.getElementById(id);
  const activator = document.getElementById(`a${id}`);
  const deactivator = document.getElementById(`d${id}`);

  if (filter && activator && deactivator) {
    filter.style.display = 'none';
    activator.style.display = '';
    deactivator.style.display = 'none';
  }

  if (sUrl) {
    ilTableJSHandler(sUrl);
  }

  return false;
}

// Success Handler
function ilTableSuccessHandler(response) {
  // parse headers function
  function parseHeaders() {
    // handle response headers if needed
  }
  parseHeaders();
}

// Failure Handler
function ilTableFailureHandler(error) {
  console.error('Request failed', error);
}

function ilTableJSHandler(sUrl) {
  fetch(sUrl, { method: 'GET' })
    .then((response) => {
      if (response.ok) {
        return response.text();
      }
      throw new Error('Network response was not ok.');
    })
    .then((data) => {
      ilTableSuccessHandler(data);
    })
    .catch((error) => {
      ilTableFailureHandler(error);
    });

  return false;
}

function ilTablePageSelection(el, cmd) {
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = cmd;
  input.value = '1';
  el.parentNode.appendChild(input);
  el.form.submit();
  return false;
}
