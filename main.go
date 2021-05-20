// Copyright 2021 Iglou.eu. All rights reserved.
// Use of this source code is governed by a MIT-style
// license that can be found in the LICENSE file.

package main

import (
	"log"

	"git.iglou.eu/Laboratory/mailSwg/internal/schema"
)

func init() {
	log.SetFlags(log.Lmicroseconds | log.Lshortfile)
}

func main() {
	schema.Loader()
}
