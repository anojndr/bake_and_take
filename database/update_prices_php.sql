-- Update product prices to Philippine Peso (₱)
-- Based on market research from Philippine bakeries (January 2024)
-- Run this script to update existing products in your database

USE bake_and_take;

-- Update prices by product slug (most reliable method)
UPDATE products SET price = 299.00 WHERE slug = 'sourdough-loaf';
UPDATE products SET price = 85.00 WHERE slug = 'butter-croissant';
UPDATE products SET price = 1350.00 WHERE slug = 'red-velvet-cake';
UPDATE products SET price = 85.00 WHERE slug = 'chocolate-chip-cookies';
UPDATE products SET price = 145.00 WHERE slug = 'baguette';
UPDATE products SET price = 145.00 WHERE slug = 'pain-au-chocolat';
UPDATE products SET price = 1250.00 WHERE slug = 'carrot-cake';
UPDATE products SET price = 950.00 WHERE slug = 'macarons-box';
UPDATE products SET price = 120.00 WHERE slug = 'ciabatta';
UPDATE products SET price = 140.00 WHERE slug = 'danish-pastry';
UPDATE products SET price = 1700.00 WHERE slug = 'chocolate-truffle-cake';
UPDATE products SET price = 75.00 WHERE slug = 'oatmeal-raisin-cookies';

-- Show updated prices
SELECT id, name, price FROM products ORDER BY id;
