-- Initial categories seed
INSERT INTO categories (slug, name) VALUES
  ('ia',       'Inteligencia Artificial'),
  ('symfony',  'Symfony'),
  ('docker',   'Docker & DevOps'),
  ('vue',      'Vue & Frontend'),
  ('general',  'General')
ON CONFLICT (slug) DO NOTHING;
