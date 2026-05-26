import boto3

def list_all_eips():
    ec2_client = boto3.client('ec2')
    regions = [region['RegionName'] for region in ec2_client.describe_regions()['Regions']]
    
    print(f"{'Region':<20} {'Public IP':<20} {'Instance ID':<20} {'Association':<20}")
    print("-" * 80)
    
    for region in regions:
        ec2 = boto3.client('ec2', region_name=region)
        try:
            addresses = ec2.describe_addresses()['Addresses']
            for addr in addresses:
                public_ip = addr.get('PublicIp', 'N/A')
                instance_id = addr.get('InstanceId', 'None')
                assoc_id = addr.get('AssociationId', 'None')
                print(f"{region:<20} {public_ip:<20} {instance_id:<20} {assoc_id:<20}")
        except Exception as e:
            print(f"Error in {region}: {e}")

if __name__ == '__main__':
    list_all_eips()
