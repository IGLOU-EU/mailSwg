use std::thread;
use std::io::{Read,Write,BufReader,BufRead};
use std::net::{TcpListener,TcpStream};

const SERVER: &str = "mailSwg";

fn main() {
    let listener = TcpListener::bind("0.0.0.0:8080").unwrap();

    for stream in listener.incoming() {
        match stream {
            Ok(stream) => {
                println!("new client!");
                println!("connection from {} to {}",
                         stream.peer_addr().unwrap(),
                         stream.local_addr().unwrap());
                thread::spawn(move|| {
                    // connection succeeded
                    //let req = &stream.try_clone().unwrap();
                    let res = &stream.try_clone().unwrap();
                    tcp_send(res, http_json_header("200 OK", "application/json", "{\"success\":true}".len()), "{\"success\":true}");
                    tcp_read(stream);
                });
            }
            Err(e) => println!("Unable to connect: {}", e),
        }
    }
}

fn tcp_send<W>(mut stream: W, header: String, rstr: &str) where W: Write {
	write!(&mut stream, "{}{}", header, rstr).unwrap();
}

fn tcp_read (mut stream: TcpStream) {
    let mut buffer = [0; 2048];
    stream.read(&mut buffer).unwrap();

    for line in String::from_utf8_lossy(&buffer[..]).lines() {
        println!(">> {}", line);
    }
}

fn http_json_header(code: &str, ctype: &str, clen: usize) -> String {
    format!("{}{}{}{}{}{}{}{}{}",
            "HTTP/1.1", code,
            "\nserver: ", SERVER,
            "\ncontent-type: ", ctype,
            "\nContent-Length: ", clen.to_string(),
            "\ndomo-arigato: Mr.Roboto\n\n"
            )
}

//header("HTTP/1.1 301 Moved Permanently");
//header("Location: https://example.com/newpage.html");
