#!/bin/bash
set -e

TIMESTAMP=$(date +%s)
BUCKET_NAME="888box-media-$TIMESTAMP"
USER_NAME="888box-s3-user"

echo "Creating Bucket: $BUCKET_NAME"
aws s3api create-bucket --bucket $BUCKET_NAME --region ap-northeast-1 --create-bucket-configuration LocationConstraint=ap-northeast-1

echo "Creating IAM User: $USER_NAME"
aws iam create-user --user-name $USER_NAME || echo "User might already exist"

echo "Creating Access Key"
KEY_JSON=$(aws iam create-access-key --user-name $USER_NAME)
ACCESS_KEY=$(echo $KEY_JSON | jq -r .AccessKey.AccessKeyId)
SECRET_KEY=$(echo $KEY_JSON | jq -r .AccessKey.SecretAccessKey)

echo "Creating Policy"
POLICY_DOC='{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": ["s3:*"],
      "Resource": [
        "arn:aws:s3:::'$BUCKET_NAME'",
        "arn:aws:s3:::'$BUCKET_NAME'/*"
      ]
    }
  ]
}'
aws iam put-user-policy --user-name $USER_NAME --policy-name 888box-s3-policy --policy-document "$POLICY_DOC"

# Disable Block Public Access to allow public-read ACLs
aws s3api put-public-access-block \
    --bucket $BUCKET_NAME \
    --public-access-block-configuration "BlockPublicAcls=false,IgnorePublicAcls=false,BlockPublicPolicy=false,RestrictPublicBuckets=false"

# Change Object Ownership to BucketOwnerPreferred so ACLs can be used
aws s3api put-bucket-ownership-controls \
    --bucket $BUCKET_NAME \
    --ownership-controls="Rules=[{ObjectOwnership=BucketOwnerPreferred}]"

# Write securely to a local file
echo "STORAGE=s3" > .env.s3
echo "S3_ENDPOINT=s3.ap-northeast-1.amazonaws.com" >> .env.s3
echo "S3_REGION=ap-northeast-1" >> .env.s3
echo "S3_BUCKET=$BUCKET_NAME" >> .env.s3
echo "S3_ACCESS_KEY_ID=$ACCESS_KEY" >> .env.s3
echo "S3_ACCESS_KEY_SECRET=$SECRET_KEY" >> .env.s3
echo "S3_CDN_DOMAIN=https://$BUCKET_NAME.s3.ap-northeast-1.amazonaws.com" >> .env.s3

echo "S3 Setup Complete!"
