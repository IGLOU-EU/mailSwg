// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package main

import (
	"log"

	"github.com/gofiber/fiber/v2"

	"git.iglou.eu/Laboratory/mailSwg/internal/config"
	"git.iglou.eu/Laboratory/mailSwg/internal/middleware"
	"git.iglou.eu/Laboratory/mailSwg/internal/schema"
)

func init() {
	log.SetFlags(log.Lmicroseconds | log.Lshortfile)
}

func main() {
	var err error

	// Load configuration
	if err = config.Data.Init(); err != nil {
		log.Fatalln(err)
	}

	// Set and init database
	if err = schema.Loader(config.Data.Db.Path); err != nil {
		log.Fatalln(err)
	}

	// HTTP Part
	app := fiber.New(fiber.Config{
		CaseSensitive: true,
		StrictRouting: true,
		ProxyHeader:   "X-Forwarded-For",
		BodyLimit:     10 * 1024 * 1024,
		ServerHeader:  "Bonjour, les pingouins",
	})

	// Accepter uniquement les requetes legitimes
	app.Post("/key/:ClientKey", middleware.GoodKey(), func(c *fiber.Ctx) error {
		return c.Status(fiber.StatusTeapot).Send([]byte("🍵 short and stout 🍵"))
	})

	// Rediriger toutes les requetes indesirables vers le repos
	app.Use(func(c *fiber.Ctx) error {
		return c.Redirect("https://git.iglou.eu/Laboratory/mailSwg")
	})

	if err := app.Listen(config.Data.HttpAdress()); err != nil {
		log.Fatalln(err)
	}
}
