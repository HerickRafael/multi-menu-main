ALTER TABLE companies
  ADD COLUMN menu_header_text_color varchar(20) DEFAULT NULL AFTER banner,
  ADD COLUMN menu_header_button_color varchar(20) DEFAULT NULL AFTER menu_header_text_color,
  ADD COLUMN menu_header_bg_color varchar(20) DEFAULT NULL AFTER menu_header_button_color,
  ADD COLUMN menu_logo_border_color varchar(20) DEFAULT NULL AFTER menu_header_bg_color,
  ADD COLUMN menu_group_title_bg_color varchar(20) DEFAULT NULL AFTER menu_logo_border_color,
  ADD COLUMN menu_group_title_text_color varchar(20) DEFAULT NULL AFTER menu_group_title_bg_color,
  ADD COLUMN menu_welcome_bg_color varchar(20) DEFAULT NULL AFTER menu_group_title_text_color,
  ADD COLUMN menu_welcome_text_color varchar(20) DEFAULT NULL AFTER menu_welcome_bg_color;
