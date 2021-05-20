// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package schema

type Email struct {
	ID     int    `xorm:"pk autoincr"`
	Client string `xorm:"notnull varchar(64)"`

	Host    string `xorm:"notnull"`
	Port    int    `xorm:"notnull"`
	Encrypt string `xorm:"notnull"`

	User string `xorm:"notnull"`
	Pass string `xorm:"notnull"`

	For      []string
	IsEnable bool `xorm:"notnull"`
}
