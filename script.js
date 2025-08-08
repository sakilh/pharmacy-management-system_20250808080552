Here is the vanilla JavaScript code for the frontend of your Pharmacy Management System. This script is designed to interact with an HTML structure that uses Tailwind CSS for styling and Line Awesome for icons, and a PHP backend for data operations.

This code assumes specific HTML element IDs and class names, which will be mentioned in the comments for clarity. It provides a foundational structure for module switching, product management (display, add, edit, delete), and a basic sales/POS system.

// Define a global App object to encapsulate all functionality, preventing global namespace pollution.
var App = {};

// -------------------------------------------------------------------
// App Configuration and Constants
// -------------------------------------------------------------------
App.config = {
  // Base URL for your PHP API endpoints. Adjust this to your backend's location.
  apiBaseUrl: '/api/pharmacy.php',

  // Default module to display on page load.
  defaultModule: 'products',

  // Message display duration in milliseconds.
  messageDisplayDuration: 3000
};

// -------------------------------------------------------------------
// DOM Element References
// These references are collected once the DOM is ready for efficiency.
// -------------------------------------------------------------------
App.ui = {
  // Main content area where different modules are displayed.
  mainContent: null,
  // Navigation buttons.
  navButtons: {}, // Will store references to nav buttons dynamically.
  // Message display area.
  messageContainer: null,

  // Product Module Elements
  productsSection: null,
  productsTableBody: null,
  addProductBtn: null,
  productFormContainer: null,
  productFormTitle: null,
  productForm: null,
  productIdInput: null,
  productNameInput: null,
  productPriceInput: null,
  productDescriptionInput: null,
  productManufacturerInput: null,
  productActiveIngredientInput: null,
  cancelProductFormBtn: null,
  productsMessage: null,

  // Sales Module Elements (POS - Point of Sale)
  salesSection: null,
  posSearchInput: null,
  posSearchResults: null,
  posCartItems: null,
  posCartTotal: null,
  processSaleBtn: null,
  salesMessage: null,
  posCustomerNameInput: null,
  posCustomerPhoneInput: null,

  // Inventory Module Elements (simplified)
  inventorySection: null,
  inventoryTableBody: null,
  inventoryMessage: null,

  // Customer Module Elements (simplified)
  customersSection: null,
  customersTableBody: null,
  addCustomerBtn: null,
  customerFormContainer: null,
  customerFormTitle: null,
  customerForm: null,
  customerIdInput: null,
  customerNameInput: null,
  customerPhoneInput: null,
  customerAddressInput: null,
  cancelCustomerFormBtn: null,
  customersMessage: null
};

// -------------------------------------------------------------------
// Utility Functions
// These functions provide common helpers for AJAX, UI updates, etc.
// -------------------------------------------------------------------
App.utils = {

  // Function to make AJAX requests to the backend API.
  // - endpoint: The specific API path (e.g., '/products', '/sales').
  // - method: HTTP method (GET, POST, PUT, DELETE).
  // - data: JavaScript object to be sent as JSON in the request body.
  // Returns a Promise that resolves with the parsed JSON response or rejects with an error.
  fetchApi: function(endpoint, method, data) {
    var url = App.config.apiBaseUrl + endpoint;
    var options = {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json' // Request JSON response
      }
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    return fetch(url, options)
      .then(function(response) {
        // Check if the response is OK (status 200-299)
        if (!response.ok) {
          // If not OK, try to parse error message from response body
          return response.json().then(function(errorData) {
            throw new Error(errorData.message || 'API request failed with status: ' + response.status);
          }).catch(function() {
            // If parsing JSON fails, just throw a generic error
            throw new Error('API request failed with status: ' + response.status);
          });
        }
        // If the response is OK, parse the JSON body
        return response.json();
      })
      .catch(function(error) {
        console.error('AJAX Error:', error);
        App.utils.displayMessage('error', error.message || 'An unexpected error occurred.');
        throw error; // Re-throw to allow further error handling down the chain
      });
  },

  // Displays a message to the user (success or error).
  // - type: 'success' or 'error'.
  // - message: The message text to display.
  displayMessage: function(type, message) {
    if (!App.ui.messageContainer) return;

    App.ui.messageContainer.textContent = message;
    App.ui.messageContainer.className = 'mt-4 p-3 rounded-md text-center'; // Reset classes

    if (type === 'success') {
      App.ui.messageContainer.classList.add('bg-green-100', 'text-green-800');
    } else if (type === 'error') {
      App.ui.messageContainer.classList.add('bg-red-100', 'text-red-800');
    }

    App.ui.messageContainer.classList.remove('hidden');

    // Hide the message after a few seconds
    setTimeout(function() {
      App.ui.messageContainer.classList.add('hidden');
      App.ui.messageContainer.textContent = ''; // Clear message content
    }, App.config.messageDisplayDuration);
  },

  // Hides all content sections and shows the specified one.
  // - moduleId: The ID of the section to show (e.g., 'products-section').
  showModule: function(moduleId) {
    // Hide all sections first
    var sections = document.querySelectorAll('.content-section'); // Assumes all module sections have this class
    sections.forEach(function(section) {
      section.classList.add('hidden');
    });

    // Show the requested section
    var targetSection = document.getElementById(moduleId);
    if (targetSection) {
      targetSection.classList.remove('hidden');

      // Update active navigation button state
      Object.keys(App.ui.navButtons).forEach(function(btnId) {
        var button = App.ui.navButtons[btnId];
        if (button.dataset.target === moduleId) {
          button.classList.add('bg-blue-700'); // Active state
          button.classList.remove('hover:bg-blue-600');
        } else {
          button.classList.remove('bg-blue-700'); // Inactive state
          button.classList.add('hover:bg-blue-600');
        }
      });

      // Trigger module-specific load function if it exists
      if (moduleId === 'products-section' && App.modules.products && App.modules.products.load) {
        App.modules.products.load();
      } else if (moduleId === 'sales-section' && App.modules.sales && App.modules.sales.init) {
        App.modules.sales.init(); // Re-initialize sales module
      } else if (moduleId === 'inventory-section' && App.modules.inventory && App.modules.inventory.load) {
        App.modules.inventory.load();
      } else if (moduleId === 'customers-section' && App.modules.customers && App.modules.customers.load) {
        App.modules.customers.load();
      }
    }
  },

  // Clears all input fields within a given form.
  // - formElement: The HTML form element to clear.
  clearForm: function(formElement) {
    if (!formElement) return;
    formElement.reset(); // Native form reset method
    // If there are hidden inputs that need clearing manually or specific fields
    var hiddenIdInput = formElement.querySelector('input[type="hidden"][name$="_id"]');
    if (hiddenIdInput) {
      hiddenIdInput.value = '';
    }
  }
};

// -------------------------------------------------------------------
// Module-Specific Logic
// Each module has its own set of functions for data fetching, rendering, and interactions.
// -------------------------------------------------------------------
App.modules = {

  // -------------------------------------------------------------------
  // Products Module
  // Handles displaying, adding, editing, and deleting products.
  // Assumed HTML IDs:
  //   - #products-section
  //   - #products-table-body
  //   - #add-product-btn
  //   - #product-form-container
  //   - #product-form-title
  //   - #product-form
  //   - #product-id (hidden input)
  //   - #product-name, #product-price, #product-description, #product-manufacturer, #product-active-ingredient
  //   - #cancel-product-form-btn
  //   - #products-message
  // -------------------------------------------------------------------
  products: {
    // Internal state for product module
    currentProductId: null, // Stores ID when editing a product

    // Loads and displays all products from the backend.
    load: function() {
      // Clear previous messages
      if (App.ui.productsMessage) App.ui.productsMessage.textContent = '';
      if (!App.ui.productsTableBody) return; // Ensure element exists

      App.ui.productsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Loading products...</td></tr>';

      App.utils.fetchApi('/products', 'GET')
        .then(function(response) {
          if (response.success && response.data) {
            App.ui.productsTableBody.innerHTML = ''; // Clear loading message
            if (response.data.length === 0) {
              App.ui.productsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No products found.</td></tr>';
              return;
            }
            response.data.forEach(function(product) {
              App.modules.products.renderProductRow(product);
            });
          } else {
            App.utils.displayMessage('error', response.message || 'Failed to load products.');
            App.ui.productsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-red-500">Error loading products.</td></tr>';
          }
        })
        .catch(function(error) {
          App.ui.productsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-red-500">Failed to connect to backend.</td></tr>';
        });
    },

    // Renders a single product row in the products table.
    // - product: The product object from the API.
    renderProductRow: function(product) {
      if (!App.ui.productsTableBody) return;

      var row = document.createElement('tr');
      row.id = 'product-row-' + product.product_id;
      row.className = 'hover:bg-gray-50'; // Tailwind hover effect

      row.innerHTML =
        '<td class="py-2 px-4 border-b">' + product.product_id + '</td>' +
        '<td class="py-2 px-4 border-b">' + product.name + '</td>' +
        '<td class="py-2 px-4 border-b">$' + parseFloat(product.price).toFixed(2) + '</td>' +
        '<td class="py-2 px-4 border-b">' + (product.manufacturer_name || 'N/A') + '</td>' +
        '<td class="py-2 px-4 border-b text-center">' +
        '  <button class="edit-product-btn bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded text-xs mr-1" data-id="' + product.product_id + '">' +
        '    <i class="la la-edit"></i> Edit' +
        '  </button>' +
        '  <button class="delete-product-btn bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs" data-id="' + product.product_id + '">' +
        '    <i class="la la-trash"></i> Delete' +
        '  </button>' +
        '</td>';

      App.ui.productsTableBody.appendChild(row);
    },

    // Handles the submission of the product form (add or edit).
    submitForm: function(event) {
      event.preventDefault(); // Prevent default form submission

      if (!App.ui.productForm) return;

      var productId = App.ui.productIdInput.value;
      var name = App.ui.productNameInput.value;
      var price = App.ui.productPriceInput.value;
      var description = App.ui.productDescriptionInput.value;
      var manufacturerId = App.ui.productManufacturerInput.value;
      var activeIngredientId = App.ui.productActiveIngredientInput.value;

      var productData = {
        name: name,
        price: parseFloat(price),
        description: description,
        manufacturer_id: manufacturerId ? parseInt(manufacturerId) : null,
        active_ingredient_id: activeIngredientId ? parseInt(activeIngredientId) : null
      };

      var method = productId ? 'PUT' : 'POST';
      var endpoint = productId ? '/products/' + productId : '/products';

      App.utils.fetchApi(endpoint, method, productData)
        .then(function(response) {
          if (response.success) {
            App.utils.displayMessage('success', response.message);
            App.modules.products.hideForm();
            App.modules.products.load(); // Reload products to show changes
          } else {
            App.utils.displayMessage('error', response.message || 'Failed to save product.');
          }
        })
        .catch(function(error) {
          // Error already handled by fetchApi, just prevent further action.
        });
    },

    // Populates the product form with existing data for editing.
    // - productId: The ID of the product to edit.
    edit: function(productId) {
      if (!App.ui.productForm || !App.ui.productFormContainer) return;

      // Show the form and change title
      App.ui.productFormContainer.classList.remove('hidden');
      App.ui.productFormTitle.textContent = 'Edit Product';

      App.ui.productsMessage.textContent = ''; // Clear any previous messages

      App.utils.fetchApi('/products/' + productId, 'GET')
        .then(function(response) {
          if (response.success && response.data) {
            var product = response.data;
            App.ui.productIdInput.value = product.product_id;
            App.ui.productNameInput.value = product.name;
            App.ui.productPriceInput.value = parseFloat(product.price).toFixed(2);
            App.ui.productDescriptionInput.value = product.description || '';
            App.ui.productManufacturerInput.value = product.manufacturer_id || '';
            App.ui.productActiveIngredientInput.value = product.active_ingredient_id || '';
          } else {
            App.utils.displayMessage('error', response.message || 'Product not found.');
            App.modules.products.hideForm(); // Hide form if product not found
          }
        })
        .catch(function(error) {
          App.modules.products.hideForm(); // Hide form on error
        });
    },

    // Sends a request to delete a product.
    // - productId: The ID of the product to delete.
    delete: function(productId) {
      if (!confirm('Are you sure you want to delete this product?')) {
        return;
      }

      App.utils.fetchApi('/products/' + productId, 'DELETE')
        .then(function(response) {
          if (response.success) {
            App.utils.displayMessage('success', response.message);
            // Remove the row from the DOM directly for immediate feedback
            var rowToRemove = document.getElementById('product-row-' + productId);
            if (rowToRemove) {
              rowToRemove.remove();
            }
          } else {
            App.utils.displayMessage('error', response.message || 'Failed to delete product.');
          }
        })
        .catch(function(error) {
          // Error already handled by fetchApi
        });
    },

    // Shows the product form for adding a new product.
    showAddForm: function() {
      if (!App.ui.productFormContainer || !App.ui.productForm) return;
      App.ui.productFormContainer.classList.remove('hidden');
      App.ui.productFormTitle.textContent = 'Add New Product';
      App.utils.clearForm(App.ui.productForm); // Clear form for new entry
      App.ui.productIdInput.value = ''; // Ensure hidden ID is empty
      App.ui.productsMessage.textContent = ''; // Clear any previous messages
    },

    // Hides the product form.
    hideForm: function() {
      if (App.ui.productFormContainer) {
        App.ui.productFormContainer.classList.add('hidden');
      }
      App.utils.clearForm(App.ui.productForm);
    },

    // Initializes event listeners for the product module.
    initEvents: function() {
      if (App.ui.addProductBtn) {
        App.ui.addProductBtn.addEventListener('click', App.modules.products.showAddForm);
      }
      if (App.ui.productForm) {
        App.ui.productForm.addEventListener('submit', App.modules.products.submitForm);
      }
      if (App.ui.cancelProductFormBtn) {
        App.ui.cancelProductFormBtn.addEventListener('click', App.modules.products.hideForm);
      }

      // Event delegation for edit and delete buttons on the products table
      if (App.ui.productsTableBody) {
        App.ui.productsTableBody.addEventListener('click', function(event) {
          var target = event.target;
          // Check if the clicked element or its parent is an edit/delete button
          var editBtn = target.closest('.edit-product-btn');
          var deleteBtn = target.closest('.delete-product-btn');

          if (editBtn) {
            var productId = editBtn.dataset.id;
            if (productId) App.modules.products.edit(productId);
          } else if (deleteBtn) {
            var productId = deleteBtn.dataset.id;
            if (productId) App.modules.products.delete(productId);
          }
        });
      }
    }
  },

  // -------------------------------------------------------------------
  // Sales Module (Point of Sale - POS)
  // Handles searching for products, adding to cart, and processing sales.
  // Assumed HTML IDs:
  //   - #sales-section
  //   - #pos-search-input
  //   - #pos-search-results
  //   - #pos-cart-items
  //   - #pos-cart-total
  //   - #process-sale-btn
  //   - #sales-message
  //   - #pos-customer-name-input
  //   - #pos-customer-phone-input
  // -------------------------------------------------------------------
  sales: {
    cart: [], // Stores items currently in the sales cart

    // Initializes the sales module UI and state.
    init: function() {
      App.modules.sales.cart = []; // Clear cart on module load
      App.modules.sales.renderCart(); // Render empty cart
      if (App.ui.posSearchInput) App.ui.posSearchInput.value = ''; // Clear search input
      if (App.ui.posSearchResults) App.ui.posSearchResults.innerHTML = ''; // Clear search results
      if (App.ui.posCustomerNameInput) App.ui.posCustomerNameInput.value = '';
      if (App.ui.posCustomerPhoneInput) App.ui.posCustomerPhoneInput.value = '';
      if (App.ui.salesMessage) App.ui.salesMessage.textContent = ''; // Clear previous messages
    },

    // Searches for products based on user input.
    searchProducts: function() {
      var query = App.ui.posSearchInput.value.trim();
      if (query.length < 2) { // Require at least 2 characters for search
        if (App.ui.posSearchResults) App.ui.posSearchResults.innerHTML = '';
        return;
      }

      // Fetch products that match the query.
      // This assumes your backend has a search endpoint or accepts a 'query' parameter.
      App.utils.fetchApi('/products?query=' + encodeURIComponent(query), 'GET')
        .then(function(response) {
          if (App.ui.posSearchResults) {
            App.ui.posSearchResults.innerHTML = '';
            if (response.success && response.data && response.data.length > 0) {
              response.data.forEach(function(product) {
                // Check if product is already in cart to adjust display/logic
                var existingCartItem = App.modules.sales.cart.find(item => item.product_id === product.product_id);
                var stockAvailable = product.stock_quantity ? product.stock_quantity - (existingCartItem ? existingCartItem.quantity : 0) : 9999; // Assume high stock if not specified

                var div = document.createElement('div');
                div.className = 'p-2 border-b border-gray-200 flex justify-between items-center';
                div.innerHTML =
                  '<span>' + product.name + ' ($' + parseFloat(product.price).toFixed(2) + ')' +
                  (product.stock_quantity !== undefined ? ' - Stock: ' + product.stock_quantity : '') +
                  '</span>' +
                  '<button class="add-to-cart-btn bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-2 rounded text-xs" data-id="' + product.product_id + '" data-name="' + product.name + '" data-price="' + product.price + '" data-stock="' + (product.stock_quantity || '') + '" ' + (stockAvailable <= 0 ? 'disabled' : '') + '>' +
                  '<i class="la la-cart-plus"></i> Add' +
                  '</button>';
                App.ui.posSearchResults.appendChild(div);
              });
            } else {
              App.ui.posSearchResults.innerHTML = '<div class="p-2 text-gray-500">No products found.</div>';
            }
          }
        })
        .catch(function(error) {
          // Error handled by fetchApi
          if (App.ui.posSearchResults) App.ui.posSearchResults.innerHTML = '<div class="p-2 text-red-500">Error searching products.</div>';
        });
    },

    // Adds a product to the sales cart.
    // - productData: An object containing product_id, name, price, stock_quantity.
    addToCart: function(productData) {
      var existingItem = App.modules.sales.cart.find(item => item.product_id === productData.product_id);
      var maxStock = productData.stock_quantity !== undefined ? parseInt(productData.stock_quantity) : Infinity;

      if (existingItem) {
        if (existingItem.quantity < maxStock) {
          existingItem.quantity++;
          App.utils.displayMessage('success', productData.name + ' quantity increased in cart.');
        } else {
          App.utils.displayMessage('error', 'Cannot add more ' + productData.name + '. Max stock reached.');
        }
      } else {
        if (maxStock > 0) {
          App.modules.sales.cart.push({
            product_id: productData.product_id,
            name: productData.name,
            price: parseFloat(productData.price),
            quantity: 1,
            stock_quantity: maxStock // Store original stock for client-side check
          });
          App.utils.displayMessage('success', productData.name + ' added to cart.');
        } else {
          App.utils.displayMessage('error', productData.name + ' is out of stock.');
        }
      }
      App.modules.sales.renderCart();
    },

    // Removes a product from the sales cart.
    // - productId: The ID of the product to remove.
    removeFromCart: function(productId) {
      App.modules.sales.cart = App.modules.sales.cart.filter(item => item.product_id !== productId);
      App.utils.displayMessage('success', 'Item removed from cart.');
      App.modules.sales.renderCart();
    },

    // Updates the quantity of an item in the cart.
    // - productId: The ID of the product.
    // - newQuantity: The new quantity to set.
    updateCartQuantity: function(productId, newQuantity) {
      var item = App.modules.sales.cart.find(i => i.product_id === productId);
      if (item) {
        var maxStock = item.stock_quantity !== undefined ? parseInt(item.stock_quantity) : Infinity;
        if (newQuantity > 0 && newQuantity <= maxStock) {
          item.quantity = newQuantity;
          App.utils.displayMessage('success', 'Quantity updated for ' + item.name + '.');
        } else if (newQuantity <= 0) {
          App.modules.sales.removeFromCart(productId); // Remove if quantity is 0 or less
          return; // Exit as item is removed
        } else {
          App.utils.displayMessage('error', 'Cannot set quantity to ' + newQuantity + '. Max stock for ' + item.name + ' is ' + maxStock + '.');
        }
        App.modules.sales.renderCart();
      }
    },

    // Renders the current state of the sales cart.
    renderCart: function() {
      if (!App.ui.posCartItems || !App.ui.posCartTotal) return;

      App.ui.posCartItems.innerHTML = '';
      var total = 0;

      if (App.modules.sales.cart.length === 0) {
        App.ui.posCartItems.innerHTML = '<li class="text-gray-500 text-center py-4">Cart is empty.</li>';
      } else {
        App.modules.sales.cart.forEach(function(item) {
          var li = document.createElement('li');
          li.className = 'flex justify-between items-center py-2 border-b border-gray-200';
          li.innerHTML =
            '<div>' +
            '  <span class="font-semibold">' + item.name + '</span><br>' +
            '  <span class="text-sm text-gray-600">$' + item.price.toFixed(2) + ' x </span>' +
            '  <input type="number" min="1" value="' + item.quantity + '" data-id="' + item.product_id + '" class="cart-quantity-input w-16 px-2 py-1 border rounded text-sm">' +
            '</div>' +
            '<div class="text-right">' +
            '  <span class="font-bold text-lg">$' + (item.price * item.quantity).toFixed(2) + '</span>' +
            '  <button class="remove-from-cart-btn bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded ml-2 text-xs" data-id="' + item.product_id + '">' +
            '    <i class="la la-times"></i>' +
            '  </button>' +
            '</div>';
          App.ui.posCartItems.appendChild(li);
          total += item.price * item.quantity;
        });
      }
      App.ui.posCartTotal.textContent = total.toFixed(2);
    },

    // Processes the sale, sending cart data to the backend.
    processSale: function() {
      if (App.modules.sales.cart.length === 0) {
        App.utils.displayMessage('error', 'Cart is empty. Please add products to process a sale.');
        return;
      }

      var customerName = App.ui.posCustomerNameInput.value.trim();
      var customerPhone = App.ui.posCustomerPhoneInput.value.trim();

      var saleData = {
        customer_name: customerName,
        customer_phone: customerPhone,
        items: App.modules.sales.cart.map(function(item) {
          return {
            product_id: item.product_id,
            quantity: item.quantity,
            price_at_sale: item.price // Record price at the time of sale
          };
        })
      };

      App.utils.fetchApi('/sales', 'POST', saleData)
        .then(function(response) {
          if (response.success) {
            App.utils.displayMessage('success', response.message + ' Sale ID: ' + response.sale_id);
            App.modules.sales.init(); // Reset POS after successful sale
            // Optionally, reload inventory to reflect stock changes
            if (App.modules.inventory && App.modules.inventory.load) {
              App.modules.inventory.load();
            }
          } else {
            App.utils.displayMessage('error', response.message || 'Failed to process sale.');
          }
        })
        .catch(function(error) {
          // Error already handled by fetchApi
        });
    },

    // Initializes event listeners for the sales module.
    initEvents: function() {
      if (App.ui.posSearchInput) {
        // Debounce the search input to prevent excessive API calls
        var searchTimeout;
        App.ui.posSearchInput.addEventListener('input', function() {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(App.modules.sales.searchProducts, 300); // 300ms debounce
        });
      }

      // Event delegation for adding to cart buttons in search results
      if (App.ui.posSearchResults) {
        App.ui.posSearchResults.addEventListener('click', function(event) {
          var target = event.target.closest('.add-to-cart-btn');
          if (target) {
            var productData = {
              product_id: parseInt(target.dataset.id),
              name: target.dataset.name,
              price: parseFloat(target.dataset.price),
              stock_quantity: target.dataset.stock ? parseInt(target.dataset.stock) : undefined
            };
            App.modules.sales.addToCart(productData);
            App.ui.posSearchInput.value = ''; // Clear search input
            App.ui.posSearchResults.innerHTML = ''; // Clear search results
          }
        });
      }

      // Event delegation for cart item actions (remove, quantity change)
      if (App.ui.posCartItems) {
        App.ui.posCartItems.addEventListener('click', function(event) {
          var target = event.target.closest('.remove-from-cart-btn');
          if (target) {
            var productId = parseInt(target.dataset.id);
            if (productId) App.modules.sales.removeFromCart(productId);
          }
        });

        App.ui.posCartItems.addEventListener('change', function(event) {
          var target = event.target.closest('.cart-quantity-input');
          if (target) {
            var productId = parseInt(target.dataset.id);
            var newQuantity = parseInt(target.value);
            if (productId && !isNaN(newQuantity)) {
              App.modules.sales.updateCartQuantity(productId, newQuantity);
            }
          }
        });
      }

      if (App.ui.processSaleBtn) {
        App.ui.processSaleBtn.addEventListener('click', App.modules.sales.processSale);
      }
    }
  },

  // -------------------------------------------------------------------
  // Inventory Module (Simplified)
  // Handles displaying inventory.
  // Assumed HTML IDs:
  //   - #inventory-section
  //   - #inventory-table-body
  //   - #inventory-message
  // -------------------------------------------------------------------
  inventory: {
    // Loads and displays inventory data.
    load: function() {
      if (!App.ui.inventoryTableBody) return;
      App.ui.inventoryTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Loading inventory...</td></tr>';
      if (App.ui.inventoryMessage) App.ui.inventoryMessage.textContent = ''; // Clear previous messages

      App.utils.fetchApi('/inventory', 'GET')
        .then(function(response) {
          if (response.success && response.data) {
            App.ui.inventoryTableBody.innerHTML = '';
            if (response.data.length === 0) {
              App.ui.inventoryTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">No inventory data found.</td></tr>';
              return;
            }
            response.data.forEach(function(item) {
              var row = document.createElement('tr');
              row.className = 'hover:bg-gray-50';
              row.innerHTML =
                '<td class="py-2 px-4 border-b">' + (item.product_name || 'N/A') + '</td>' +
                '<td class="py-2 px-4 border-b">' + item.batch_number + '</td>' +
                '<td class="py-2 px-4 border-b">' + item.expiry_date + '</td>' +
                '<td class="py-2 px-4 border-b">' + item.quantity + '</td>';
              App.ui.inventoryTableBody.appendChild(row);
            });
          } else {
            App.utils.displayMessage('error', response.message || 'Failed to load inventory.');
            App.ui.inventoryTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-red-500">Error loading inventory.</td></tr>';
          }
        })
        .catch(function(error) {
          App.ui.inventoryTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-red-500">Failed to connect to backend.</td></tr>';
        });
    }
    // Add functions for updating stock, adding new inventory batches etc. if needed.
  },

  // -------------------------------------------------------------------
  // Customers Module (Simplified)
  // Handles displaying customers.
  // Assumed HTML IDs:
  //   - #customers-section
  //   - #customers-table-body
  //   - #add-customer-btn
  //   - #customer-form-container
  //   - #customer-form-title
  //   - #customer-form
  //   - #customer-id (hidden input)
  //   - #customer-name, #customer-phone, #customer-address
  //   - #cancel-customer-form-btn
  //   - #customers-message
  // -------------------------------------------------------------------
  customers: {
    // Loads and displays all customers from the backend.
    load: function() {
      if (!App.ui.customersTableBody) return;
      App.ui.customersTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">Loading customers...</td></tr>';
      if (App.ui.customersMessage) App.ui.customersMessage.textContent = ''; // Clear previous messages

      App.utils.fetchApi('/customers', 'GET')
        .then(function(response) {
          if (response.success && response.data) {
            App.ui.customersTableBody.innerHTML = ''; // Clear loading message
            if (response.data.length === 0) {
              App.ui.customersTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">No customers found.</td></tr>';
              return;
            }
            response.data.forEach(function(customer) {
              App.modules.customers.renderCustomerRow(customer);
            });
          } else {
            App.utils.displayMessage('error', response.message || 'Failed to load customers.');
            App.ui.customersTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-red-500">Error loading customers.</td></tr>';
          }
        })
        .catch(function(error) {
          App.ui.customersTableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-red-500">Failed to connect to backend.</td></tr>';
        });
    },

    // Renders a single customer row in the customers table.
    renderCustomerRow: function(customer) {
      if (!App.ui.customersTableBody) return;

      var row = document.createElement('tr');
      row.id = 'customer-row-' + customer.customer_id;
      row.className = 'hover:bg-gray-50';

      row.innerHTML =
        '<td class="py-2 px-4 border-b">' + customer.customer_id + '</td>' +
        '<td class="py-2 px-4 border-b">' + customer.name + '</td>' +
        '<td class="py-2 px-4 border-b">' + (customer.phone || 'N/A') + '</td>' +
        '<td class="py-2 px-4 border-b text-center">' +
        '  <button class="edit-customer-btn bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded text-xs mr-1" data-id="' + customer.customer_id + '">' +
        '    <i class="la la-edit"></i> Edit' +
        '  </button>' +
        '  <button class="delete-customer-btn bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs" data-id="' + customer.customer_id + '">' +
        '    <i class="la la-trash"></i> Delete' +
        '  </button>' +
        '</td>';

      App.ui.customersTableBody.appendChild(row);
    },

    // Handles the submission of the customer form (add or edit).
    submitForm: function(event) {
      event.preventDefault();

      if (!App.ui.customerForm) return;

      var customerId = App.ui.customerIdInput.value;
      var name = App.ui.customerNameInput.value;
      var phone = App.ui.customerPhoneInput.value;
      var address = App.ui.customerAddressInput.value;

      var customerData = {
        name: name,
        phone: phone,
        address: address
      };

      var method = customerId ? 'PUT' : 'POST';
      var endpoint = customerId ? '/customers/' + customerId : '/customers';

      App.utils.fetchApi(endpoint, method, customerData)
        .then(function(response) {
          if (response.success) {
            App.utils.displayMessage('success', response.message);
            App.modules.customers.hideForm();
            App.modules.customers.load(); // Reload customers to show changes
          } else {
            App.utils.displayMessage('error', response.message || 'Failed to save customer.');
          }
        })
        .catch(function(error) {
          // Error already handled
        });
    },

    // Populates the customer form with existing data for editing.
    edit: function(customerId) {
      if (!App.ui.customerForm || !App.ui.customerFormContainer) return;

      App.ui.customerFormContainer.classList.remove('hidden');
      App.ui.customerFormTitle.textContent = 'Edit Customer';
      App.ui.customersMessage.textContent = '';

      App.utils.fetchApi('/customers/' + customerId, 'GET')
        .then(function(response) {
          if (response.success && response.data) {
            var customer = response.data;
            App.ui.customerIdInput.value = customer.customer_id;
            App.ui.customerNameInput.value = customer.name;
            App.ui.customerPhoneInput.value = customer.phone || '';
            App.ui.customerAddressInput.value = customer.address || '';
          } else {
            App.utils.displayMessage('error', response.message || 'Customer not found.');
            App.modules.customers.hideForm();
          }
        })
        .catch(function(error) {
          App.modules.customers.hideForm();
        });
    },

    // Sends a request to delete a customer.
    delete: function(customerId) {
      if (!confirm('Are you sure you want to delete this customer?')) {
        return;
      }

      App.utils.fetchApi('/customers/' + customerId, 'DELETE')
        .then(function(response) {
          if (response.success) {
            App.utils.displayMessage('success', response.message);
            var rowToRemove = document.getElementById('customer-row-' + customerId);
            if (rowToRemove) {
              rowToRemove.remove();
            }
          } else {
            App.utils.displayMessage('error', response.message || 'Failed to delete customer.');
          }
        })
        .catch(function(error) {
          // Error already handled
        });
    },

    // Shows the customer form for adding a new customer.
    showAddForm: function() {
      if (!App.ui.customerFormContainer || !App.ui.customerForm) return;
      App.ui.customerFormContainer.classList.remove('hidden');
      App.ui.customerFormTitle.textContent = 'Add New Customer';
      App.utils.clearForm(App.ui.customerForm);
      App.ui.customerIdInput.value = '';
      App.ui.customersMessage.textContent = '';
    },

    // Hides the customer form.
    hideForm: function() {
      if (App.ui.customerFormContainer) {
        App.ui.customerFormContainer.classList.add('hidden');
      }
      App.utils.clearForm(App.ui.customerForm);
    },

    // Initializes event listeners for the customer module.
    initEvents: function() {
      if (App.ui.addCustomerBtn) {
        App.ui.addCustomerBtn.addEventListener('click', App.modules.customers.showAddForm);
      }
      if (App.ui.customerForm) {
        App.ui.customerForm.addEventListener('submit', App.modules.customers.submitForm);
      }
      if (App.ui.cancelCustomerFormBtn) {
        App.ui.cancelCustomerFormBtn.addEventListener('click', App.modules.customers.hideForm);
      }

      if (App.ui.customersTableBody) {
        App.ui.customersTableBody.addEventListener('click', function(event) {
          var target = event.target;
          var editBtn = target.closest('.edit-customer-btn');
          var deleteBtn = target.closest('.delete-customer-btn');

          if (editBtn) {
            var customerId = editBtn.dataset.id;
            if (customerId) App.modules.customers.edit(customerId);
          } else if (deleteBtn) {
            var customerId = deleteBtn.dataset.id;
            if (customerId) App.modules.customers.delete(customerId);
          }
        });
      }
    }
  }
};

// -------------------------------------------------------------------
// Main Application Initialization
// This function runs once the DOM is fully loaded.
// -------------------------------------------------------------------
App.init = function() {
  // 1. Collect DOM element references
  App.ui.mainContent = document.getElementById('main-content'); // Main container for all modules
  App.ui.messageContainer = document.getElementById('app-message'); // Global message area

  // Navigation buttons (assumed to have data-target attribute pointing to module section ID)
  document.querySelectorAll('.nav-button').forEach(function(button) {
    App.ui.navButtons[button.id] = button;
  });

  // Product Module UI elements
  App.ui.productsSection = document.getElementById('products-section');
  App.ui.productsTableBody = document.getElementById('products-table-body');
  App.ui.addProductBtn = document.getElementById('add-product-btn');
  App.ui.productFormContainer = document.getElementById('product-form-container');
  App.ui.productFormTitle = document.getElementById('product-form-title');
  App.ui.productForm = document.getElementById('product-form');
  App.ui.productIdInput = document.getElementById('product-id');
  App.ui.productNameInput = document.getElementById('product-name');
  App.ui.productPriceInput = document.getElementById('product-price');
  App.ui.productDescriptionInput = document.getElementById('product-description');
  App.ui.productManufacturerInput = document.getElementById('product-manufacturer');
  App.ui.productActiveIngredientInput = document.getElementById('product-active-ingredient');
  App.ui.cancelProductFormBtn = document.getElementById('cancel-product-form-btn');
  App.ui.productsMessage = document.getElementById('products-message');

  // Sales Module UI elements
  App.ui.salesSection = document.getElementById('sales-section');
  App.ui.posSearchInput = document.getElementById('pos-search-input');
  App.ui.posSearchResults = document.getElementById('pos-search-results');
  App.ui.posCartItems = document.getElementById('pos-cart-items');
  App.ui.posCartTotal = document.getElementById('pos-cart-total');
  App.ui.processSaleBtn = document.getElementById('process-sale-btn');
  App.ui.salesMessage = document.getElementById('sales-message');
  App.ui.posCustomerNameInput = document.getElementById('pos-customer-name-input');
  App.ui.posCustomerPhoneInput = document.getElementById('pos-customer-phone-input');

  // Inventory Module UI elements
  App.ui.inventorySection = document.getElementById('inventory-section');
  App.ui.inventoryTableBody = document.getElementById('inventory-table-body');
  App.ui.inventoryMessage = document.getElementById('inventory-message');

  // Customer Module UI elements
  App.ui.customersSection = document.getElementById('customers-section');
  App.ui.customersTableBody = document.getElementById('customers-table-body');
  App.ui.addCustomerBtn = document.getElementById('add-customer-btn');
  App.ui.customerFormContainer = document.getElementById('customer-form-container');
  App.ui.customerFormTitle = document.getElementById('customer-form-title');
  App.ui.customerForm = document.getElementById('customer-form');
  App.ui.customerIdInput = document.getElementById('customer-id');
  App.ui.customerNameInput = document.getElementById('customer-name');
  App.ui.customerPhoneInput = document.getElementById('customer-phone');
  App.ui.customerAddressInput = document.getElementById('customer-address');
  App.ui.cancelCustomerFormBtn = document.getElementById('cancel-customer-form-btn');
  App.ui.customersMessage = document.getElementById('customers-message');


  // 2. Attach Global Event Listeners
  // Navigation listeners
  Object.values(App.ui.navButtons).forEach(function(button) {
    button.addEventListener('click', function() {
      var targetModuleId = button.dataset.target; // Use data-target attribute for module ID
      if (targetModuleId) {
        App.utils.showModule(targetModuleId);
      }
    });
  });

  // 3. Initialize Module-Specific Event Listeners
  App.modules.products.initEvents();
  App.modules.sales.initEvents();
  App.modules.customers.initEvents();
  // No specific events for inventory in this simplified version, just load on show.

  // 4. Load the default module on page load
  App.utils.showModule(App.config.defaultModule + '-section'); // e.g., 'products-section'
};

// Ensure the DOM is fully loaded before initializing the app.
document.addEventListener('DOMContentLoaded', App.init);

/*
  -------------------------------------------------------------------
  Assumed HTML Structure (for context, not part of JS output)
  -------------------------------------------------------------------

  You would typically have an index.html file structured something like this:

  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Pharmacy Management System</title>
      <!-- Tailwind CSS CDN (for development) -->
      <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
      <!-- Line Awesome CDN for icons -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
  </head>
  <body class="bg-gray-100 font-sans">
      <div class="min-h-screen flex flex-col">
          <!-- Header/Navigation -->
          <header class="bg-blue-800 text-white p-4 shadow-md">
              <div class="container mx-auto flex justify-between items-center">
                  <h1 class="text-2xl font-bold">Pharmacy PMS</h1>
                  <nav>
                      <ul class="flex space-x-4">
                          <li><button id="nav-products" data-target="products-section" class="nav-button bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300"><i class="la la-pills"></i> Products</button></li>
                          <li><button id="nav-sales" data-target="sales-section" class="nav-button bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300"><i class="la la-cash-register"></i> Sales (POS)</button></li>
                          <li><button id="nav-inventory" data-target="inventory-section" class="nav-button bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300"><i class="la la-boxes"></i> Inventory</button></li>
                          <li><button id="nav-customers" data-target="customers-section" class="nav-button bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300"><i class="la la-users"></i> Customers</button></li>
                      </ul>
                  </nav>
              </div>
          </header>

          <!-- Global Message Area -->
          <div id="app-message" class="hidden container mx-auto mt-4 p-3 rounded-md text-center"></div>

          <!-- Main Content Area -->
          <main id="main-content" class="flex-grow container mx-auto p-4">

              <!-- Products Section -->
              <div id="products-section" class="content-section bg-white p-6 rounded-lg shadow-md hidden">
                  <h2 class="text-2xl font-semibold mb-4">Product Management</h2>
                  <button id="add-product-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4"><i class="la la-plus"></i> Add New Product</button>

                  <div id="product-form-container" class="hidden mt-4 p-4 border rounded-lg bg-gray-100">
                      <h3 id="product-form-title" class="text-xl font-semibold mb-2">Add Product</h3>
                      <form id="product-form">
                          <input type="hidden" id="product-id" name="product_id">
                          <div class="mb-4">
                              <label for="product-name" class="block text-gray-700 text-sm font-bold mb-2">Product Name:</label>
                              <input type="text" id="product-name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                          </div>
                          <div class="mb-4">
                              <label for="product-price" class="block text-gray-700 text-sm font-bold mb-2">Price:</label>
                              <input type="number" id="product-price" name="price" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                          </div>
                          <div class="mb-4">
                              <label for="product-description" class="block text-gray-700 text-sm font-bold mb-2">Description:</label>
                              <textarea id="product-description" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3"></textarea>
                          </div>
                          <div class="mb-4">
                              <label for="product-manufacturer" class="block text-gray-700 text-sm font-bold mb-2">Manufacturer ID:</label>
                              <input type="number" id="product-manufacturer" name="manufacturer_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                          </div>
                          <div class="mb-4">
                              <label for="product-active-ingredient" class="block text-gray-700 text-sm font-bold mb-2">Active Ingredient ID:</label>
                              <input type="number" id="product-active-ingredient" name="active_ingredient_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                          </div>
                          <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"><i class="la la-save"></i> Save Product</button>
                          <button type="button" id="cancel-product-form-btn" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ml-2"><i class="la la-times"></i> Cancel</button>
                      </form>
                  </div>

                  <div class="mt-8">
                      <table class="min-w-full bg-white border border-gray-200">
                          <thead>
                              <tr>
                                  <th class="py-2 px-4 border-b">ID</th>
                                  <th class="py-2 px-4 border-b">Name</th>
                                  <th class="py-2 px-4 border-b">Price</th>
                                  <th class="py-2 px-4 border-b">Manufacturer</th>
                                  <th class="py-2 px-4 border-b">Actions</th>
                              </tr>
                          </thead>
                          <tbody id="products-table-body">
                              <!-- Product rows will be injected here by JavaScript -->
                          </tbody>
                      </table>
                      <div id="products-message" class="mt-4 text-center"></div>
                  </div>
              </div>

              <!-- Sales/POS Section -->
              <div id="sales-section" class="content-section bg-white p-6 rounded-lg shadow-md hidden">
                  <h2 class="text-2xl font-semibold mb-4">Point of Sale (POS)</h2>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <!-- Product Search and Add -->
                      <div>
                          <h3 class="text-xl font-semibold mb-3">Find Products</h3>
                          <input type="text" id="pos-search-input" placeholder="Search product by name..." class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline mb-3">
                          <div id="pos-search-results" class="border rounded max-h-60 overflow-y-auto bg-gray-50">
                              <!-- Search results will appear here -->
                          </div>
                      </div>

                      <!-- Cart and Checkout -->
                      <div>
                          <h3 class="text-xl font-semibold mb-3">Sales Cart</h3>
                          <ul id="pos-cart-items" class="border rounded min-h-[150px] max-h-60 overflow-y-auto bg-gray-50 p-2 mb-3">
                              <!-- Cart items will appear here -->
                          </ul>
                          <div class="flex justify-between items-center text-xl font-bold border-t pt-3 mt-3">
                              <span>Total:</span>
                              <span>$<span id="pos-cart-total">0.00</span></span>
                          </div>

                          <h3 class="text-xl font-semibold mt-6 mb-3">Customer Information (Optional)</h3>
                          <div class="mb-3">
                              <label for="pos-customer-name-input" class="block text-gray-700 text-sm font-bold mb-2">Customer Name:</label>
                              <input type="text" id="pos-customer-name-input" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                          </div>
                          <div class="mb-4">
                              <label for="pos-customer-phone-input" class="block text-gray-700 text-sm font-bold mb-2">Customer Phone:</label>
                              <input type="text" id="pos-customer-phone-input" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                          </div>

                          <button id="process-sale-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded w-full text-lg mt-4"><i class="la la-check-circle"></i> Process Sale</button>
                          <div id="sales-message" class="mt-4 text-center"></div>
                      </div>
                  </div>
              </div>

              <!-- Inventory Section -->
              <div id="inventory-section" class="content-section bg-white p-6 rounded-lg shadow-md hidden">
                  <h2 class="text-2xl font-semibold mb-4">Inventory Management</h2>
                  <div class="mt-8">
                      <table class="min-w-full bg-white border border-gray-200">
                          <thead>
                              <tr>
                                  <th class="py-2 px-4 border-b">Product Name</th>
                                  <th class="py-2 px-4 border-b">Batch Number</th>
                                  <th class="py-2 px-4 border-b">Expiry Date</th>
                                  <th class="py-2 px-4 border-b">Quantity</th>
                              </tr>
                          </thead>
                          <tbody id="inventory-table-body">
                              <!-- Inventory rows will be injected here by JavaScript -->
                          </tbody>
                      </table>
                      <div id="inventory-message" class="mt-4 text-center"></div>
                  </div>
              </div>

              <!-- Customers Section -->
              <div id="customers-section" class="content-section bg-white p-6 rounded-lg shadow-md hidden">
                  <h2 class="text-2xl font-semibold mb-4">Customer Management</h2>
                  <button id="add-customer-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4"><i class="la la-user-plus"></i> Add New Customer</button>

                  <div id="customer-form-container" class="hidden mt-4 p-4 border rounded-lg bg-gray-100">
                      <h3 id="customer-form-title" class="text-xl font-semibold mb-2">Add Customer</h3>
                      <form id="customer-form">
                          <input type="hidden" id="customer-id" name="customer_id">
                          <div class="mb-4">
                              <label for="customer-name" class="block text-gray-700 text-sm font-bold mb-2">Customer Name:</label>
                              <input type="text" id="customer-name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                          </div>
                          <div class="mb-4">
                              <label for="customer-phone" class="block text-gray-700 text-sm font-bold mb-2">Phone Number:</label>
                              <input type="text" id="customer-phone" name="phone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                          </div>
                          <div class="mb-4">
                              <label for="customer-address" class="block text-gray-700 text-sm font-bold mb-2">Address:</label>
                              <textarea id="customer-address" name="address" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="3"></textarea>
                          </div>
                          <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"><i class="la la-save"></i> Save Customer</button>
                          <button type="button" id="cancel-customer-form-btn" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ml-2"><i class="la la-times"></i> Cancel</button>
                      </form>
                  </div>

                  <div class="mt-8">
                      <table class="min-w-full bg-white border border-gray-200">
                          <thead>
                              <tr>
                                  <th class="py-2 px-4 border-b">ID</th>
                                  <th class="py-2 px-4 border-b">Name</th>
                                  <th class="py-2 px-4 border-b">Phone</th>
                                  <th class="py-2 px-4 border-b">Actions</th>
                              </tr>
                          </thead>
                          <tbody id="customers-table-body">
                              <!-- Customer rows will be injected here by JavaScript -->
                          </tbody>
                      </table>
                      <div id="customers-message" class="mt-4 text-center"></div>
                  </div>
              </div>

          </main>

          <!-- Footer (Optional) -->
          <footer class="bg-gray-800 text-white p-4 text-center mt-auto">
              <div class="container mx-auto">
                  <p>&copy; 2023 Pharmacy Management System. All rights reserved.</p>
              </div>
          </footer>

      </div>

      <!-- Your JavaScript file -->
      <script src="script.js"></script>
  </body>
  </html>
*/