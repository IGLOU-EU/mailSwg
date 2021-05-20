// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package schema

import (
	"time"

	mail "github.com/xhit/go-simple-mail"
)

type Email struct {
	ID     int    `xorm:"pk autoincr"`
	Client string `xorm:"notnull varchar(64)"`

	Host    string `xorm:"notnull"`
	Port    int    `xorm:"notnull"`
	Encrypt string `xorm:"notnull"`

	User string `xorm:"notnull"`
	Pass string `xorm:"notnull"`

	For      []string
	From     string `xorm:"-"`
	IsEnable bool   `xorm:"notnull"`

	client *mail.SMTPClient
	server *mail.SMTPServer
}

func (e *Email) New(from string) error {
	var err error

	// Config the smtp server
	e.server.Host = e.Host
	e.server.Port = e.Port
	e.server.Username = e.User
	e.server.Password = e.Pass
	e.server.KeepAlive = true
	e.server.ConnectTimeout = 10 * time.Second
	e.server.SendTimeout = 10 * time.Second

	switch e.Encrypt {
	case "tls":
		e.server.Encryption = mail.EncryptionTLS
	case "ssl":
		e.server.Encryption = mail.EncryptionSSL
	default:
		e.server.Encryption = mail.EncryptionNone
	}

	// Try the SMTP server
	if e.client, err = e.server.Connect(); err != nil {
		return err
	}
	return e.client.Close()
}

func (e *Email) connect() error {
	var err error

	if e.client, err = e.server.Connect(); err != nil {
		return err
	}

	return nil
}

func (e *Email) close() error {
	return e.client.Close()
}

func (e *Email) Send(subject, message string) error {
	email := mail.NewMSG()

	email.SetFrom(e.From).SetSubject(subject)
	email.SetBody(mail.TextPlain, message)

	if len(e.For) == 1 {
		email.AddTo(e.For...)
	} else {
		email.AddBcc(e.For...)
	}

	if err := e.connect(); err != nil {
		return err
	}
	defer e.close()

	return email.Send(e.client)
}
