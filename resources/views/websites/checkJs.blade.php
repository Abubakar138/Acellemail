// Define the URL for the POST request
const url = '{{ action('WebsiteController@checkJs', [
    'uid' => $website->uid,
]) }}';

// Make a GET request using fetch
fetch(url)
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    // Handle the data from the response
    console.log(data);
  })
  .catch(error => {
    // Handle errors during the request
    console.error('Fetch error:', error);
  });