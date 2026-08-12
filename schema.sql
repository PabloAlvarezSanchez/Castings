CREATE TABLE usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usu_usuario VARCHAR(50) NOT NULL UNIQUE,
  usu_clave VARCHAR(32) NOT NULL,       -- MD5
  usu_derechos VARCHAR(50) NOT NULL DEFAULT '1',
  usu_reintento INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE casting (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  descripcion TEXT,
  fecha_apertura DATE NOT NULL,
  fecha_cierre DATE NOT NULL,
  estado ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- actor = persona (identidad unica por email), independiente del casting
CREATE TABLE actor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  telefono VARCHAR(30) NOT NULL,
  fecha_nacimiento DATE NOT NULL,
  altura DECIMAL(4,1) NOT NULL,
  medidas VARCHAR(60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- inscripcion = una persona presentandose a un casting concreto
CREATE TABLE inscripcion (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_id INT NOT NULL,
  casting_id INT NOT NULL,
  fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
  CONSTRAINT fk_inscripcion_actor FOREIGN KEY (actor_id) REFERENCES actor(id),
  CONSTRAINT fk_inscripcion_casting FOREIGN KEY (casting_id) REFERENCES casting(id),
  UNIQUE KEY uq_actor_casting (actor_id, casting_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- material subido para una inscripcion concreta (cada casting puede pedir fotos/video distintos)
CREATE TABLE actor_media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  inscripcion_id INT NOT NULL,
  tipo ENUM('foto','video') NOT NULL,
  ruta_fichero VARCHAR(255) NOT NULL,
  CONSTRAINT fk_media_inscripcion FOREIGN KEY (inscripcion_id) REFERENCES inscripcion(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE config (
  clave VARCHAR(50) PRIMARY KEY,
  valor VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO config (clave, valor) VALUES
  ('nombre_sitio', 'Castings'),
  ('email_contacto', ''),
  ('texto_pie', '');

-- Usuario admin inicial (cambiar la clave tras el primer login)
INSERT INTO usuario (usu_usuario, usu_clave, usu_derechos)
VALUES ('admin', MD5('cambiar-esta-clave'), '1');
