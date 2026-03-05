#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import re
import socket
import requests
import sys
from concurrent.futures import ThreadPoolExecutor, as_completed
import base64
import os

def get_nodes_from_subscription(subscription_url):
    """
    Gets a list of nodes from subscription
    """
    try:
        # Add User-Agent header for some subscriptions
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        
        response = requests.get(subscription_url, headers=headers, timeout=10)
        response.raise_for_status()
        
        # Try to decode as base64 (usually subscriptions are in base64)
        try:
            import base64
            # Decode base64
            decoded_content = base64.b64decode(response.text).decode('utf-8')
            lines = decoded_content.strip().split('\n')
        except:
            # If not base64, just split by lines
            lines = response.text.strip().split('\n')
        
        return lines
    
    except Exception as e:
        print(f"Error getting subscription: {e}")
        return []

def extract_domains_from_connection_strings(connection_strings):
    """
    Extracts domain names from connection strings
    Supports different formats: vless://, vmess://, trojan://, ss://
    """
    domains = set()
    
    for conn_str in connection_strings:
        if not conn_str.strip():
            continue
        
        # Способ 1: Извлекаем host из параметров запроса
        host_match = re.search(r'host=([^&#]+)', conn_str)
        if host_match:
            domains.add(host_match.group(1))
            continue
        
        # Способ 2: Извлекаем домен после @ (как запасной вариант)
        at_match = re.search(r'@([^:/?#]+)', conn_str)
        if at_match:
            domains.add(at_match.group(1))
    
    return list(domains)


def resolve_domain_to_ip(domain, timeout=3):
    """
    Resolves domain to IP address
    """
    # The '0' for port is a placeholder when only address info is needed
    # and the flags=socket.AI_ADDRCONFIG helps to only return addresses
    # configured on the local system (more reliable).
    addr_info = socket.getaddrinfo(domain, 0, family=socket.AF_UNSPEC, flags=socket.AI_ADDRCONFIG)
    ipv4_addresses = set()
    ipv6_addresses = set()
    for family, _, _, _, sockaddr in addr_info:
        ip_address = sockaddr[0]
        if family == socket.AF_INET:
            ipv4_addresses.add(ip_address)
        elif family == socket.AF_INET6:
            ipv6_addresses.add(ip_address)
    return domain, [*list(ipv4_addresses), *list(ipv6_addresses)]


def get_all_ips(subscription_url):
    """
    Main function: gets all IPs from subscription
    """
    print(f"Getting subscription: {subscription_url}")
    
    # Get connection strings
    connection_strings = get_nodes_from_subscription(subscription_url)
    
    if not connection_strings:
        print("Could not get subscription or subscription is empty")
        return []
    
    print(f"Found connection strings: {len(connection_strings)}")
    # Extract domains
    domains = extract_domains_from_connection_strings(connection_strings)
    
    if not domains:
        print("Could not extract domains from connection strings")
        return []
    
    # Resolve domains to IPs (in parallel)
    ips = []
    with ThreadPoolExecutor(max_workers=4) as executor:
        future_to_domain = {executor.submit(resolve_domain_to_ip, domain): domain 
                           for domain in domains}
        
        for future in as_completed(future_to_domain):
            domain, ip_list = future.result()
            if ip_list:
                ips.extend(ip_list)
                print(f"✓ {domain} -> {', '.join(ip_list)}")
            else:
                print(f"✗ {domain} -> not resolving")
    
    # Remove duplicate IPs
    unique_ips = list(set(ips))
    
    return unique_ips

def save_ips_to_file(ips, filename=None):
    """
    Saves IPs to file
    """
    if filename is None:
        # Save file next to the executable
        script_dir = os.path.dirname(os.path.abspath(__file__))
        filename = os.path.join(script_dir, "node_ips.txt")
    
    with open(filename, 'w') as f:
        for ip in sorted(ips):
            f.write(f"{ip}\n")
    print(f"IP addresses saved to file: {filename}")

def main():
    # Insert your subscription URL here
    subscription_url = "https://portal.firestarter.click/sub/3c0dd467-05fd-4b3a-aa1a-b9e3d4a650a1" 
    
    if len(sys.argv) > 1:
        subscription_url = sys.argv[1]
    
    ips = get_all_ips(subscription_url)
    
    if ips:
        print(f"✅ Found unique IP addresses: {len(ips)}")

        # Save to file
        save_ips_to_file(ips)
    else:
        print("❌ Could not get IP addresses")

if __name__ == "__main__":
    main()
    