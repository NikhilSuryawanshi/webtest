# webtest

## Go to linkedin 

https://www.linkedin.com/posts/nikhil-suryawanshi-7a08841a2_terraform-awscloud-cloudfront-activity-6679048533238198273-WOHf

# the code file 

# login
provider "aws" {
  profile = "Nikhil"
  region = "ap-south-1"
}

# create key
resource "aws_key_pair" "mykey" {
  key_name   = "mykey"
  public_key = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCml88NDD4RiOOs4YBQ9grzvYG/eZB55IapQuIkYJkAGbGoQ7m3sPGl+KKFVnOIciJkyeAJ/YaUoF7bljadW7zc5t1cXkTeyCIu+/66jD1GSoTxr58IHFv6iZ5JCpvzozonjowS6HWjkpNpP1A+ziXk/XF8hHkdEd57S8kE/NNPF/0kwTJJ6lEvo0LQuop44yIRyYEQPnvEpk6zPL76BQSrP8LnCjYTxuF3PJxyS2QPcxpakkFe5fF8sm3JDR0Dqi4PaMXvd07JRfB5oiEni4dLPIqjLEeaZbNjEf+NZ1ipRnFtReK1tLr example@gmail.com"
}

# creating  security group

resource "aws_security_group" "allow_ports" {
  name        = "allow_ports"
  description = "Allow  inbound traffic"
  

  ingress {
    description = "tcp from VPC"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
ingress {
    description = "ssh from VPC"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "allow_ports"
  }
}
# creating instance 

resource "aws_instance" "myinstance" {
  ami           = "ami-0447a12f28fddb066"
  instance_type = "t2.micro"
  key_name = "mykey"
  security_groups = ["${aws_security_group.allow_ports.name}"]
 
connection {
    type     = "ssh"
    user     = "ec2-user"
   private_key = file("C:/Users/Preadiator/Desktop/Terraform/mykey.pem")
    host     = aws_instance.myinstance.public_ip
  }

   provisioner "remote-exec" {
    inline = [
      "sudo yum install httpd php git -y",
      "sudo systemctl restart httpd",
      "sudo systemctl enable httpd",
    ]
  }
  tags = {
    Name = "Webserver"
  }
}

# output of instance (availablity zone & public ip & instance id)

output "instance_az" {
       value = aws_instance.myinstance.availability_zone
}

output "instance_public_ip" {
       value = aws_instance.myinstance.public_ip
}

output "instance_id" {
       value = aws_instance.myinstance.id
}

# creating volume

resource "aws_ebs_volume" "ebs1" {
  availability_zone = aws_instance.myinstance.availability_zone
  size              = 1

  tags = {
    Name = "myebs1"
  }
}

# output for volume (volume id)

output "ebs_vol_id" {
       value = aws_ebs_volume.ebs1.id
}

#attaching volume

resource "aws_volume_attachment" "ebs_att" {
  device_name = "/dev/sdd"
  volume_id   = aws_ebs_volume.ebs1.id
  instance_id = aws_instance.myinstance.id
  force_detach = true
}

# create bucket
resource "aws_s3_bucket" "ns29bucket" {
  bucket = "ns29bucket"
  acl    = "private"
  region = "ap-south-1"

  tags = {
    Name   = "ns29bucket"
    
  }
}

locals {
  s3_origin_id = "myS3_bucket_Origin"
}


# change permission

resource "aws_s3_bucket_public_access_block" "example" {
  bucket = "ns29bucket"

  block_public_acls   = false
  block_public_policy = false
}

# creating cloud fornt

resource "aws_cloudfront_distribution" "cloud_dist" {
  origin {
    domain_name = aws_s3_bucket.ns29bucket.bucket_regional_domain_name
    origin_id   = local.s3_origin_id

    s3_origin_config {
  origin_access_identity ="${aws_cloudfront_origin_access_identity.origin_access_identity.cloudfront_access_identity_path}"
  }

  }

  enabled             = true
  default_root_object = "index.html"


  default_cache_behavior {
    allowed_methods  = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cached_methods   = ["GET", "HEAD"]
    target_origin_id = local.s3_origin_id

    forwarded_values {
      query_string = true

      cookies {
        forward = "none"
      }
    }

    viewer_protocol_policy = "allow-all"
    min_ttl                = 0
    default_ttl            = 3600
    max_ttl                = 86400
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
     
    }
  }
 depends_on = [ aws_s3_bucket_policy.mypolicy ]
  
  viewer_certificate {
    cloudfront_default_certificate = true
  }
}


output "cloudfront_domain_name" {
       value = aws_cloudfront_distribution.cloud_dist.domain_name
}


#Making CloudFront Origin access Identity
resource "aws_cloudfront_origin_access_identity" "origin_access_identity" {
  comment = "Some comment"
  depends_on = [ aws_s3_bucket.ns29bucket ]
}


#Updating IAM policies in bucket
data "aws_iam_policy_document" "s3_policy" {
  statement {
    actions   = ["s3:GetObject"]
    resources = ["${aws_s3_bucket.ns29bucket.arn}/*"]


    principals {
      type        = "AWS"
      identifiers = ["${aws_cloudfront_origin_access_identity.origin_access_identity.iam_arn}"]
    }
  }


  statement {
    actions   = ["s3:ListBucket"]
    resources = ["${aws_s3_bucket.ns29bucket.arn}"]


    principals {
      type        = "AWS"
      identifiers = ["${aws_cloudfront_origin_access_identity.origin_access_identity.iam_arn}"]
    }
  }
  depends_on = [ aws_cloudfront_origin_access_identity.origin_access_identity ]
}


# Updating Bucket Policies
resource "aws_s3_bucket_policy" "mypolicy" {
  bucket = "${aws_s3_bucket.ns29bucket.id}"
  policy = "${data.aws_iam_policy_document.s3_policy.json}"
  depends_on = [ aws_cloudfront_origin_access_identity.origin_access_identity ]

}




resource "null_resource" "nullremote3"  {

depends_on = [
    aws_volume_attachment.ebs_att,aws_cloudfront_distribution.cloud_dist,
  ]


 connection {
    type     = "ssh"
    user     = "ec2-user"
    private_key = file("C:/Users/Preadiator/Desktop/Terraform/mykey.pem")
    host     = aws_instance.myinstance.public_ip
  }

provisioner "remote-exec" {
    inline = [
      "sudo mkfs.ext4  /dev/xvdh",
      "sudo mount  /dev/xvdh  /var/www/html",
      "sudo rm -rf /var/www/html/*",
      "sudo git clone https://github.com/NikhilSuryawanshi/webtest.git /var/www/html/",
	  "sudo su << EOF",
      "echo 'http://${aws_cloudfront_distribution.cloud_dist.domain_name}/${aws_s3_bucket_object.ns29bucket.key}' > /var/www/html/url.txt",
      "EOF",
	  
    ]
  }
}

# upload image
resource "aws_s3_bucket_object" "ns29bucket" {
depends_on = [
    aws_s3_bucket.ns29bucket,
  ]
  bucket = "ns29bucket"
  key    = "image.jpg"
  source = "C:/Users/Preadiator/Pictures/image.jpg"
  etag = filemd5("C:/Users/Preadiator/Pictures/image.jpg")
  acl = "public-read"
  content_type = "image/jpg"

}

resource "null_resource" "nulllocal1"  {


depends_on = [
    null_resource.nullremote3,
  ]


provisioner "local-exec" {
	    command = "firefox  ${aws_instance.myinstance.public_ip}"
  	}
}

# save this file with .tf extension
