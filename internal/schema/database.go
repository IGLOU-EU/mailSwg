// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package schema

import (
	"time"

	"xorm.io/xorm"
	"xorm.io/xorm/names"

	_ "github.com/mattn/go-sqlite3"
)

var xe *xorm.Engine

func Loader(dbPath string) error {
	var err error

	xe, err = xorm.NewEngine("sqlite3", dbPath)
	if err != nil {
		return err
	}

	xe.SetMapper(names.GonicMapper{})
	xe.TZLocation, _ = time.LoadLocation("UTC")

	return xe.CreateTables(
		new(Client),
		new(Webhook),
		new(Email),
	)
}
