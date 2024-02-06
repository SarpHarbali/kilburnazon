CREATE TABLE IF NOT EXISTS product (
    product_id INT PRIMARY KEY,
    name VARCHAR(255),
    description VARCHAR(500),
    price DECIMAL(10,2),
    quantity_left INT
    );

CREATE TABLE IF NOT EXISTS address (
    address_id INT NOT NULL AUTO_INCREMENT,
    house_number INT NOT NULL,
    street_name VARCHAR(100) NOT NULL,
    city VARCHAR(100),
    post_code VARCHAR(8),
    PRIMARY KEY (address_id)
    );

CREATE TABLE IF NOT EXISTS customer (
    customer_id INT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email_address VARCHAR(255),
    address_id INT,
    FOREIGN KEY (address_id) REFERENCES address(address_id)
    );

CREATE TABLE IF NOT EXISTS customer_order (
    order_id INT PRIMARY KEY,
    purchase_date DATE,
    customer_id INT,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id)
    );

CREATE TABLE IF NOT EXISTS ordered_product (
    order_id INT,
    product_id INT,
    quantity INT,
    PRIMARY KEY (order_id, product_id),
    FOREIGN KEY (order_id) REFERENCES customer_order(order_id),
    FOREIGN KEY (product_id) REFERENCES product(product_id)
    );

CREATE TABLE IF NOT EXISTS area (
    area_id INT PRIMARY KEY,
    area_name VARCHAR(255)
    );

CREATE TABLE IF NOT EXISTS office (
    office_id INT PRIMARY KEY,
    office_name VARCHAR(255),
    office_location VARCHAR(255),
    area_id INT,
    FOREIGN KEY (area_id) REFERENCES area(area_id)
    );


CREATE TABLE IF NOT EXISTS emergency_contact (
    emergency_contact_id INT NOT NULL AUTO_INCREMENT,
    emergency_name VARCHAR(255) NOT NULL,
    emergency_relationship VARCHAR(100) NOT NULL,
    emergency_phone VARCHAR (100) NOT NULL,
    PRIMARY KEY (emergency_contact_id)
    );

CREATE TABLE IF NOT EXISTS department (
    department_name VARCHAR(20) PRIMARY KEY,
    number_of_employees INT,
    head_office_location VARCHAR(255),
    manager_id INT NULL
    );

CREATE TABLE IF NOT EXISTS employee (
    emp_id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address_id INT,
    salary DECIMAL(10,2),
    dob DATE,
    nin VARCHAR(10),
    department VARCHAR(20),
    emergency_contact_id INT,
    INDEX (name),
    FOREIGN KEY (department) REFERENCES department(department_name),
    FOREIGN KEY (address_id) REFERENCES address(address_id),
    FOREIGN KEY (emergency_contact_id) REFERENCES emergency_contact(emergency_contact_id)	

    );



CREATE TABLE IF NOT EXISTS manager (
    manager_id INT,
    emp_id VARCHAR(10),
    office_id INT,
    PRIMARY KEY (manager_id, emp_id),
    FOREIGN KEY (office_id) REFERENCES office(office_id),
    FOREIGN KEY (emp_id) REFERENCES employee(emp_id)				
    );

ALTER TABLE department
    ADD FOREIGN KEY (manager_id) REFERENCES manager(manager_id);		


CREATE TABLE IF NOT EXISTS hr_member (
    hr_member_id INT,
    emp_id VARCHAR(10),
    office_id INT,
    PRIMARY KEY (hr_member_id, emp_id),
    FOREIGN KEY (emp_id) REFERENCES employee(emp_id),
    FOREIGN KEY (office_id) REFERENCES office(office_id)
    );

CREATE TABLE IF NOT EXISTS complaint (
    complaint_number INT PRIMARY KEY,
    complaint_date DATE,
    complaint_reason varchar(500),
    customer_id INT,
    hr_member_id INT,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id),
    FOREIGN KEY (hr_member_id) REFERENCES hr_member(hr_member_id)
    );

CREATE TABLE IF NOT EXISTS packager (
    packager_id INT,
    emp_id VARCHAR(10),
    area_id INT,
    PRIMARY KEY (packager_id, emp_id),
    FOREIGN KEY (emp_id) REFERENCES employee(emp_id),
    FOREIGN KEY (area_id) REFERENCES area(area_id)
    );

CREATE TABLE IF NOT EXISTS route (
    route_id INT PRIMARY KEY,
    route_name VARCHAR(255),
    starting_location VARCHAR(255) NOT NULL,
    ending_location VARCHAR(255) NOT NULL,
    final_arrival_time TIME
    );

CREATE TABLE IF NOT EXISTS stop (
    stop_id INT PRIMARY KEY,
    stop_location VARCHAR(255),
    stop_arrival_time TIME,
    route_id INT,
    FOREIGN KEY (route_id) REFERENCES route(route_id)
    );

CREATE TABLE IF NOT EXISTS vehicle (
    vehicle_id INT PRIMARY KEY,
    vehicle_name VARCHAR(255),
    area_id INT,
    FOREIGN KEY (area_id) REFERENCES area(area_id)
    );


CREATE TABLE IF NOT EXISTS driver (
    driver_id INT,
    emp_id VARCHAR(10),
    hours_per_week INT,
    area_id INT,
    vehicle_id INT,
    route_id INT,
    PRIMARY KEY (driver_id, emp_id),
    FOREIGN KEY (emp_id) REFERENCES employee(emp_id),
    FOREIGN KEY (area_id) REFERENCES area(area_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicle(vehicle_id),
    FOREIGN KEY (route_id) REFERENCES route(route_id)
    );

CREATE TABLE IF NOT EXISTS warehouse (
    warehouse_id INT PRIMARY KEY,
    warehouse_location VARCHAR(255),
    warehouse_size INT,
    warehouse_purpose VARCHAR(255),
    area_id INT,
    FOREIGN KEY (area_id) REFERENCES area(area_id)
    );

CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id_deleted VARCHAR(10),
    deletion_date DATE,
    deleted_by_employee_id VARCHAR(10)
);