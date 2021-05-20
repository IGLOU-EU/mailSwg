// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package middleware

import (
	"log"

	"github.com/gofiber/fiber/v2"

	"git.iglou.eu/Laboratory/mailSwg/pkg/tool"
)

func GoodKey() fiber.Handler {
	return func(c *fiber.Ctx) error {
		q := c.Params("ClientKey")
		log.Println(q, len(q), tool.IsAlphaNum(q))
		if len(q) != 64 || !tool.IsAlphaNum(q) {
			return c.Redirect("https://git.iglou.eu/Laboratory/mailSwg")
		}
		return c.Next()
	}
}
