const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

export const environment = {
  URL: window.location.protocol + '//' + window.location.hostname + (isLocalhost ? ':8000' : ''),
};
