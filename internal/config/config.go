// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package config

import (
	"encoding/json"
	"flag"
	"fmt"
	"io/ioutil"
	"os"
)

var Data Config

// Config est la structure du fichier de configuration
type Config struct {
	HTTP struct {
		Host string `json:"host"`
		Port string `json:"port"`
	} `json:"http"`
	Db struct {
		Path string `json:"path"`
	} `json:"db"`
}

func (c *Config) Init() error {
	var configFile, httpHost, httpPort string

	// Parsing cli flag
	flag.StringVar(&httpHost, "host", "", "Force host config `0.0.0.0`")
	flag.StringVar(&httpPort, "port", "", "Force port config `8080`")
	flag.StringVar(&configFile, "config", "./config/config.json", "Set configuration file location")

	flag.Parse()

	// Set configuration
	if err := c.load(configFile); err != nil {
		return err
	}
	if httpHost != "" {
		c.HTTP.Host = httpHost
	}
	if httpPort != "" {
		c.HTTP.Port = httpPort
	}

	return nil
}

func (c *Config) load(fp string) error {
	cf, err := ioutil.ReadFile(fp)
	if os.IsNotExist(err) {
		return err
	}

	err = json.Unmarshal(cf, &c)
	if err != nil {
		return fmt.Errorf("file `%s` Unmarshal error\n> %s", fp, err)
	}

	return nil
}

func (c Config) HttpAdress() string {
	return fmt.Sprintf("%s:%s", c.HTTP.Host, c.HTTP.Port)
}
