USE handmade_goods;

-- Drop the existing foreign key constraint
ALTER TABLE ORDER_ITEMS 
DROP FOREIGN KEY order_items_ibfk_2;

-- Add the new foreign key constraint with ON DELETE SET NULL
ALTER TABLE ORDER_ITEMS 
ADD CONSTRAINT order_items_item_id_fk 
FOREIGN KEY (item_id) 
REFERENCES ITEMS(id) 
ON DELETE SET NULL;