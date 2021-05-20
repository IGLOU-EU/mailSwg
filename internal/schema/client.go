// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package schema

import "time"

type Client struct {
	// Client title+name+key hash
	ID string `xorm:"pk varchar(64)"`

	// Title is site form title
	Title string `xorm:"notnull"`

	// To activate pseudo honney pot
	Honeypot bool `xorm:"notnull"`
	// Need all acceptable input are present
	FullSuccess bool `xorm:"notnull"`
	// List of acceptable input name
	Acceptable []string

	// Redirect to . when success
	Success string `xorm:"notnull"`
	// Redirect to . when success
	Fail string `xorm:"notnull"`

	Created time.Time `xorm:"INDEX created"`
}
