use std::thread;
use std::io::{Read,Write};
use std::net::{TcpListener,TcpStream};

const SERVER: &str = "mailSwg";

fn main() {
    let listener = TcpListener::bind("0.0.0.0:8080").unwrap();

    for stream in listener.incoming() {
        match stream {
            Ok(stream) => {
                println!("new client!");
                println!("connection from {} to {}", stream.peer_addr().unwrap(), stream.local_addr().unwrap());
                thread::spawn(move|| {handle_connection(stream);});
            }
            Err(e) => println!("Unable to connect: {}", e),
        }
    }
}

fn handle_connection(mut stream: TcpStream) {
    // Get data
    let mut request   = String::new();
    let mut post_data = String::new();

    let mut buffer = Vec::new();
    {
    let mut handle = <TcpStream as Read>::by_ref(&mut stream);
    handle.take(450).read_to_end(&mut buffer).unwrap();
    }

    for line in String::from_utf8_lossy(&buffer[..]).lines() {
        if request.is_empty() { request = line.to_string(); }
        
        post_data.clear();
        post_data.push_str(line.trim());

        println!(">> {}", line);
    }

    // Post data
    let rstr = format!("{}{}{}{}{}", "{\"success\":true, \"id\":\"", request, "\", \"request\":\"", post_data, "\"}");
    let response = format!("{}{}",
                            http_header("200 OK", "application/json", rstr.len()),
                            rstr
                          );

    stream.write(response.as_bytes()).unwrap();
    stream.flush().unwrap();
}

fn http_header(code: &str, ctype: &str, clen: usize) -> String {
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
