CREATE TABLE players (
                         player_id SERIAL PRIMARY KEY,
                         username VARCHAR(255) NOT NULL
);

CREATE TABLE games (
                              id SERIAL PRIMARY KEY,
                              player_id INT,
                              kronus INT DEFAULT 0,
                              lyrion INT DEFAULT 0,
                              mystara INT DEFAULT 0,
                              eclipsia INT DEFAULT 0,
                              fiora INT DEFAULT 0,
                              score INT DEFAULT 0,
                              status VARCHAR(50) DEFAULT 'in_progress',
                              FOREIGN KEY (player_id) REFERENCES players(player_id)
);