CREATE TABLE tbl_crm_user (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    user_pass VARCHAR(255) NOT NULL,
    user_department VARCHAR(100),
    user_type VARCHAR(50),
    user_status VARCHAR(50)
);

CREATE TABLE Manufacturers (
    ManufacturerID INT PRIMARY KEY AUTO_INCREMENT,
    ManufacturerName VARCHAR(255) NOT NULL UNIQUE,
    Address VARCHAR(500),
    ContactPerson VARCHAR(255),
    PhoneNumber VARCHAR(50),
    Email VARCHAR(255)
);

CREATE TABLE ActiveIngredients (
    IngredientID INT PRIMARY KEY AUTO_INCREMENT,
    IngredientName VARCHAR(255) NOT NULL UNIQUE,
    ChemicalFormula VARCHAR(255),
    TherapeuticClass VARCHAR(255)
);

CREATE TABLE Suppliers (
    SupplierID INT PRIMARY KEY AUTO_INCREMENT,
    SupplierName VARCHAR(255) NOT NULL UNIQUE,
    ContactPerson VARCHAR(255),
    PhoneNumber VARCHAR(50)
);

CREATE TABLE Customers (
    CustomerID INT PRIMARY KEY AUTO_INCREMENT,
    CustomerName VARCHAR(255) NOT NULL,
    Address VARCHAR(500),
    PhoneNumber VARCHAR(50)
);

CREATE TABLE Products (
    ProductID INT PRIMARY KEY AUTO_INCREMENT,
    ProductName VARCHAR(255) NOT NULL,
    GenericName VARCHAR(255),
    Strength VARCHAR(100),
    PharmaceuticalForm VARCHAR(100),
    RouteOfAdministration VARCHAR(100),
    ManufacturerID INT NOT NULL,
    ATC_Code VARCHAR(50),
    Description TEXT,
    PrescriptionRequired BOOLEAN DEFAULT FALSE,
    DrugIdentificationNumber VARCHAR(100) UNIQUE,
    FOREIGN KEY (ManufacturerID) REFERENCES Manufacturers(ManufacturerID)
);

CREATE TABLE ProductIngredients (
    ProductID INT NOT NULL,
    IngredientID INT NOT NULL,
    QuantityPerUnit VARCHAR(100),
    PRIMARY KEY (ProductID, IngredientID),
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID),
    FOREIGN KEY (IngredientID) REFERENCES ActiveIngredients(IngredientID)
);

CREATE TABLE Inventory (
    InventoryID INT PRIMARY KEY AUTO_INCREMENT,
    ProductID INT NOT NULL,
    BatchNumber VARCHAR(100) NOT NULL,
    ExpiryDate DATE NOT NULL,
    QuantityInStock INT NOT NULL DEFAULT 0,
    Location VARCHAR(100),
    CostPrice DECIMAL(10,2) NOT NULL,
    SellingPrice DECIMAL(10,2) NOT NULL,
    UNIQUE (ProductID, BatchNumber),
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID)
);

CREATE TABLE PurchaseOrders (
    OrderID INT PRIMARY KEY AUTO_INCREMENT,
    SupplierID INT NOT NULL,
    OrderDate DATE NOT NULL,
    ExpectedDeliveryDate DATE,
    Status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID)
);

CREATE TABLE OrderDetails (
    OrderDetailID INT PRIMARY KEY AUTO_INCREMENT,
    OrderID INT NOT NULL,
    ProductID INT NOT NULL,
    OrderedQuantity INT NOT NULL,
    ReceivedQuantity INT DEFAULT 0,
    UnitPriceAtPurchase DECIMAL(10,2) NOT NULL,
    UNIQUE (OrderID, ProductID),
    FOREIGN KEY (OrderID) REFERENCES PurchaseOrders(OrderID),
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID)
);

CREATE TABLE Sales (
    SaleID INT PRIMARY KEY AUTO_INCREMENT,
    SaleDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CustomerID INT,
    TotalAmount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID)
);

CREATE TABLE SaleDetails (
    SaleDetailID INT PRIMARY KEY AUTO_INCREMENT,
    SaleID INT NOT NULL,
    ProductID INT NOT NULL,
    QuantitySold INT NOT NULL,
    UnitPriceAtSale DECIMAL(10,2) NOT NULL,
    UNIQUE (SaleID, ProductID),
    FOREIGN KEY (SaleID) REFERENCES Sales(SaleID),
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID)
);