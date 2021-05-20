// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package schema

import (
	"crypto/sha256"
	"encoding/hex"
	"time"
)

type Client struct {
	// Client title+name+success hash
	ID string `xorm:"pk varchar(64)"`
	// Enable or disable account
	Activate bool `xorm:"notnull"`

	// Client name
	Name string `xorm:"notnull"`
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

	// Account creation date
	Created time.Time `xorm:"INDEX created"`
}

func GetClient(id string) (Client, error) {
	var c Client
	var _, err = xe.ID(id).Get(&c)

	return c, err
}

func DelClient(id string) error {
	_, err := xe.ID(id).Delete(&Client{})

	return err
}

func (c *Client) regenID() {
	sum := sha256.Sum256([]byte(c.Name + c.Title + c.Success))
	c.ID = hex.EncodeToString(sum[:])
}

func (c *Client) Add() error {
	c.regenID()

	_, err := xe.InsertOne(c)

	return err
}

func (c *Client) Update() error {
	oID := c.ID
	c.regenID()

	_, err := xe.ID(oID).Update(c)

	return err
}

func (c *Client) Del() error {
	_, err := xe.Delete(c)

	return err
}

func (c *Client) Enable() error {
	c.Activate = true

	_, err := xe.Update(c)

	return err
}

func (c *Client) Disable() error {
	c.Activate = false

	_, err := xe.Update(c)

	return err
}

func (c *Client) IsEnable() bool {
	return c.Activate
}

func (c *Client) IsUsable() bool {
	return (c.Success != "" && c.Fail != "")
}
