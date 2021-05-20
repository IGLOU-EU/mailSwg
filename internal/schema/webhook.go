// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package schema

type Webhook struct {
	ID     int    `xorm:"pk autoincr"`
	Client string `xorm:"notnull varchar(64)"`

	URL    string   `xorm:"notnull"`
	Header []string `xorm:"notnull"`
	Data   string   `xorm:"notnull"`

	IsEnable bool `xorm:"notnull"`
}
