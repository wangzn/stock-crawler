<?php
class Store
{
    private $addr_info;

    public function init($addr)
    {
        $this->addr_info = parse_url($addr);
    }

    /*
    curl -i -XPOST 'http://localhost:8086/write?db=mydb'
    --data-binary 'cpu_load_short,host=server01,region=us-west value=0.64 1434055562000000000'
     */
    public function insert($data)
    {
        $data = $this->format($data);
        $url = $this->get_http_request_url();
        $body = $this->get_data_binary($data);
        $result = http_request($url, $body);
        return $result;
    }

    private function format($data)
    {
    }

    private function get_http_request_url()
    {
        extract($this->addr_info);
        $url = sprintf("%s://%s:%s/write?db=%s", $scheme, $host, $port, $db);
        return $url;
    }

    private function get_data_binary($data)
    {
        $series = "stock-crawler";
        $tag = influx_tag_list($data);
        $value = influx_value_list($data);
        $ts = influx_ts($data);
        $str = sprintf("%s,%s %s %s", $series, $tag, $value, $ts);
        return $str;
    }

    private function influx_tag_list($data)
    {
        $str = sprintf("id=%s", $data["stock_number"]);
        return $str;
    }

    private function influx_value_list($data)
    {
        foreach ($data["value"] as $k => $v) {
            $arr[] = sprintf("%s=%f", $k, $v);
        }
        return implode(",", $arr);
    }

    private function influx_ts($data)
    {
        $ts = strtotime($data["date"]);
        return $ts."000000000";
    }
}
